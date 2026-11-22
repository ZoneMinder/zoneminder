<?php

$root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';

class App {
    public static function uses($a, $b) {}
}
class AppModel {}
function passwordProblem($p) { return null; }
function passwordProblemText($p) { return ''; }

require_once "$root/web/api/app/Model/User.php";

$failures = 0;

function fmt($v) {
    if (is_bool($v)) {
        return $v ? 'true' : 'false';
    }
    return json_encode($v);
}

function check($name, $got, $want) {
    global $failures;
    if ($got === $want) {
        echo "ok - $name\n";
    } else {
        echo "FAIL - $name (got ".fmt($got).", want ".fmt($want).")\n";
        $failures++;
    }
}

function missingRequiredOnCreate(array $validate, array $data) {
    $missing = array();
    foreach ($validate as $field => $rules) {
        if (!is_array($rules)) {
            continue;
        }
        $requiredOnCreate = false;
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            if (isset($rule['required']) && ($rule['required'] === true || $rule['required'] === 'create')) {
                $requiredOnCreate = true;
                break;
            }
        }
        if ($requiredOnCreate && !isset($data[$field])) {
            $missing[] = $field;
        }
    }
    return $missing;
}

$u = new User();
$rules = $u->validate;

$missing1 = missingRequiredOnCreate($rules, array());
check('empty body requires Username', in_array('Username', $missing1, true), true);
check('empty body requires Password', in_array('Password', $missing1, true), true);

$missing2 = missingRequiredOnCreate($rules, array('Username' => 'bob'));
check('provided Username is not reported missing', in_array('Username', $missing2, true), false);
check('missing Password is reported', in_array('Password', $missing2, true), true);

$missing3 = missingRequiredOnCreate($rules, array('Username' => 'bob', 'Password' => 'secret'));
check('all required fields provided yields no missing fields', $missing3, array());

$forcedOnUpdate = array();
foreach ($rules as $field => $fieldRules) {
    if (!is_array($fieldRules)) {
        continue;
    }
    foreach ($fieldRules as $rule) {
        if (is_array($rule) && isset($rule['required']) && $rule['required'] === true) {
            $forcedOnUpdate[] = $field;
            break;
        }
    }
}
check('no field is forced required on update', $forcedOnUpdate, array());

exit($failures ? 1 : 0);
