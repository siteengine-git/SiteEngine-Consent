<?php
// Unmodified recordEntryDetails method excerpt, Fluent Forms 6.2.16.
// Source: app/Services/Submission/SubmissionService.php
// SHA-256 a0a4f43fc1d019c813fd5e21cfa0f4fc89122331dae5430ad1a74fe15270cd75
// GPLv2-or-later, WPManageNinja. Dependencies below are isolated test doubles.
namespace ConsentFixture;
function maybe_serialize($value) { return is_array($value) || is_object($value) ? serialize($value) : $value; }
class Arr { public static function except($data, $keys) { return array_diff_key($data, array_flip($keys)); } }
class Helper { public static function getWhiteListedFields($id) { return []; } }
class EntryDetails { public static $rows = []; public static function insert($rows) { self::$rows = array_merge(self::$rows, $rows); } }
class SubmissionService {
    public function recordEntryDetails($entryId, $formId, $data)
    {
        $formData = Arr::except($data, Helper::getWhiteListedFields($formId));

        $entryItems = [];
        foreach ($formData as $dataKey => $dataValue) {
            if ($dataValue === '' || $dataValue === null) {
                continue;
            }

            if (is_array($dataValue) || is_object($dataValue)) {
                foreach ($dataValue as $subKey => $subValue) {
                    if ($subValue === '' || $subValue === null || $subValue === [] || $subValue === false) {
                        continue;
                    }
                    $entryItems[] = [
                        'form_id'        => $formId,
                        'submission_id'  => $entryId,
                        'field_name'     => trim($dataKey),
                        'sub_field_name' => $subKey,
                        'field_value'    => maybe_serialize($subValue),
                    ];
                }
            } else {
                $entryItems[] = [
                    'form_id'        => $formId,
                    'submission_id'  => $entryId,
                    'field_name'     => trim($dataKey),
                    'sub_field_name' => '',
                    'field_value'    => $dataValue,
                ];
            }
        }

        if ($entryItems) {
            EntryDetails::insert($entryItems);
        }

        return true;
    }
}
