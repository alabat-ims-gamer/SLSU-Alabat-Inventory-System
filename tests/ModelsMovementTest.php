<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
require_once ROOT_PATH . '/app/Helpers/Autoloader.php';
\App\Helpers\Autoloader::register();

use App\Config\Database;
use App\Models\Item;
use App\Models\PropertyMovement;
use App\Models\LostProperty;

echo "--- Running ModelsMovementTest ---\n";

$db = Database::getConnection();

// 1. Create a dummy test item
$created = Item::createBatch($db, [
    'item_name'      => 'Test Projector ' . time(),
    'category'       => 'IT Equipment',
    'location'       => 'Science Lab',
    'item_condition' => 'mint',
], 1, 1);

assert(!empty($created), "Failed to create test item");
$itemId = (int)$created[0]['item_id'];
echo "[+] Created test item ID: {$itemId}\n";

// 2. Test Property Movement logging via Item::updateItem()
$updateRes = Item::updateItem($db, $itemId, [
    'item_name'       => $created[0]['item_name'],
    'category'        => 'IT Equipment',
    'location'        => 'GIS & COMLAB',
    'transfer_reason' => 'Lab Setup Relocation',
    'item_condition'  => 'mint',
], null, 1);

assert($updateRes['success'] === true, "Item update should succeed");
$movements = PropertyMovement::getHistoryByItem($db, $itemId);
assert(count($movements) >= 1, "Should have at least 1 movement record");
assert($movements[0]['previous_location'] === 'Science Lab', "Previous location must be Science Lab");
assert($movements[0]['new_location'] === 'GIS & COMLAB', "New location must be GIS & COMLAB");
assert($movements[0]['reason'] === 'Lab Setup Relocation', "Reason must match");
echo "[+] Movement recorded automatically on location change!\n";

// 3. Test Lost Property reporting via Item::reportLost()
$lostRes = Item::reportLost($db, $itemId, [
    'accountable_person'   => 'Prof. Valderama',
    'date_reported'        => date('Y-m-d'),
    'description'          => 'Unit not found during inventory check',
    'investigation_status' => 'under_investigation'
], 1);

assert($lostRes === true, "Item::reportLost should return true");
$refreshed = Item::findById($db, $itemId);
assert($refreshed['status'] === 'lost', "Item status should now be 'lost'");

$lostReports = LostProperty::getReports($db, 'IT Equipment', 10);
$found = false;
foreach ($lostReports as $lr) {
    if ((int)$lr['item_id'] === $itemId) {
        $found = true;
        assert($lr['accountable_person'] === 'Prof. Valderama');
        assert($lr['investigation_status'] === 'under_investigation');
        break;
    }
}
assert($found === true, "Lost report must be retrievable via LostProperty::getReports()");
echo "[+] Lost property reported and status updated to 'lost'!\n";

// 4. Cleanup test item (cascades to movements and lost_properties)
$db->exec("DELETE FROM items WHERE item_id = {$itemId}");
echo "[+] Cleaned up test item\n";
echo "SUCCESS: All model tests PASSED!\n";
