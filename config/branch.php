<?php
// Branch config utility
$branches = [
    'Matain Branch' => [
        'address' => 'Matain, Subic, Zambales',
        'display' => 'Matain Branch',
        'full' => 'Matain Branch – Matain, Subic, Zambales',
        'contact' => '0981-243-6970',
    ],
    'San Isidro Main Branch' => [
        'address' => 'National Govic Highway, San Isidro,Subic, Zambales',
        'display' => 'San Isidro Main Branch',
        'full' => 'San Isidro Main Branch – National Govic Highway, San Isidro, Zambales',
        'contact' => '0912-321-0987',
    ],
    'Sawmill Branch' => [
        'address' => 'Sawmill, Subic, Zambales',
        'display' => 'Sawmill Branch',
        'full' => 'Sawmill Branch – Sawmill Area, Subic, Zambales',
        'contact' => '0922-888-9876',
    ],
];
$branchFile = __DIR__ . '/active_branch.json';
$activeBranch = array_keys($branches)[0];
if (file_exists($branchFile)) {
    $data = json_decode(file_get_contents($branchFile), true);
    if (isset($data['branch']) && isset($branches[$data['branch']])) {
        $activeBranch = $data['branch'];
    }
}
$activeBranchData = $branches[$activeBranch];

// Keep session branch in sync when config is loaded
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION['branch'] = $activeBranch;
}
