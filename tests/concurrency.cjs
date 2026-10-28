const fs = require('node:fs');
const path = require('node:path');
const { spawn } = require('node:child_process');
const assert = require('node:assert/strict');
const php = process.argv[2];
if (!php) throw new Error('Pass an existing PHP CLI executable. No server/database is used.');
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let count = 0;
function check(condition, name) { assert.ok(condition, name); count++; console.log(`PASS ${name}`); }
async function scenario(name, requests) {
  const root = fs.mkdtempSync(path.join(__dirname, '.concurrency-'));
  const workers = [];
  try {
    const results = requests.map(([event, choice], i) => {
      const child = spawn(php, ['-n', path.join(__dirname, 'concurrency-worker.php'), root, String(i), String(event), choice], { windowsHide: true });
      workers.push(child);
      return new Promise((resolve, reject) => {
        let output = '', error = '';
        child.stdout.on('data', chunk => output += chunk); child.stderr.on('data', chunk => error += chunk);
        child.on('error', reject);
        child.on('close', code => { if (code !== 0 || error) reject(new Error(`Worker failed: ${code}: ${error}: ${output}`)); else { try { resolve(JSON.parse(output)); } catch (e) { reject(e); } } });
      });
    });
    // Register rejection handling immediately while waiting for the common start barrier.
    const complete = Promise.all(results); complete.catch(() => {});
    const deadline = Date.now() + 10000;
    while (!requests.every((_, i) => fs.existsSync(path.join(root, `ready-${i}`)))) {
      if (Date.now() > deadline) throw new Error('Workers did not reach barrier');
      await sleep(10);
    }
    fs.writeFileSync(path.join(root, 'start'), 'start');
    const out = await complete;
    const rows = JSON.parse(fs.readFileSync(path.join(root, 'rows.json'), 'utf8'));
    check(new Set(out.map(x => x.pid)).size === requests.length, `${name}: separate PHP workers reached common barrier`);
    return { out, rows };
  } finally {
    for (const child of workers) if (child.exitCode === null) child.kill();
    // This is our generated scratch folder only, resolved inside this test directory.
    const resolved = fs.realpathSync(root), parent = fs.realpathSync(__dirname);
    if (path.dirname(resolved) !== parent || !path.basename(resolved).startsWith('.concurrency-')) throw new Error('Unsafe fixture cleanup path');
    fs.rmSync(resolved, { recursive: true, force: true, maxRetries: 10, retryDelay: 100 });
  }
}
(async () => {
  let r = await scenario('identical retries', [[1, 'grant'], [1, 'grant']]);
  check(r.out.every(x => x.state === 'verified') && r.rows.length === 1, 'concurrent identical retries produce one genuine acknowledged row');
  check(r.out[0].row_id === r.out[1].row_id && r.out[0].receipt_id === r.out[1].receipt_id, 'both concurrent retries resolve the same immutable event');
  check(r.out.some(x => x.wait_ms > 0), 'same-event requests actually contend on the shared named-lock adapter');
  r = await scenario('conflicting reuse', [[2, 'grant'], [2, 'reject']]);
  check(r.rows.length === 1 && r.out.filter(x => x.error === 'event_conflict').length === 1 && r.out.filter(x => x.state === 'verified').length === 1, 'concurrent contradictory replay has one winner and one explicit conflict, no appended authority');
  r = await scenario('distinct interactions', [[3, 'reject'], [4, 'grant']]);
  check(r.rows.length === 2 && r.out.every(x => x.state === 'verified') && r.out[0].receipt_id !== r.out[1].receipt_id, 'parallel distinct interactions preserve separate events');
  check(JSON.parse(r.rows[0].consent_json).advertising !== JSON.parse(r.rows[1].consent_json).advertising, 'parallel accept/reject choices retain their own stored category values');
  console.log(`Concurrency checks: ${count} passed using actual parallel PHP processes and temporary file-backed storage. MySQL GET_LOCK semantics are not exercised.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
