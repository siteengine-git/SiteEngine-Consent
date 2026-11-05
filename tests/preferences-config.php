<?php
$source=file_get_contents(dirname(__DIR__).'/siteengine-consent.php');
preg_match('/    private function preference_controls\(\): array \{(.*?)\n    \}/s',$source,$m);
if(!$m)throw new RuntimeException('configuration method absent');
function apply_filters($hook,$default){if($hook!=='siteengine_consent_preference_controls')throw new RuntimeException('wrong hook');return $GLOBALS['input'];}
eval('class Preference_Config_Fixture { public function read(): array {'.$m[1].'} }');
$fixture=new Preference_Config_Fixture();$count=0;
foreach ([null, 'bad', [], ['mode'=>'evil','side'=>'top','bottom'=>'bad'], ['mode'=>'custom','side'=>'left','bottom'=>900], ['bottom'=>-1]] as $input) {
 $GLOBALS['input']=$input;$v=$fixture->read();
 if (!in_array($v['mode'],['auto','custom'],true)||!in_array($v['side'],['left','right'],true)||$v['bottom']<8||$v['bottom']>320)throw new RuntimeException('unsafe presentation config');$count++;
}
if($v['bottom']!==8)throw new RuntimeException('lower clamp failed');
$GLOBALS['input']=['mode'=>'custom','side'=>'left','bottom'=>120];if($fixture->read()!==$GLOBALS['input'])throw new RuntimeException('supported config mismatch');$count++;
echo $count.' presentation configuration checks passed.'.PHP_EOL;
