<?php
/**
 * Customer Management API
 * Handles customers, loyalty, and support tickets
 */

require_once '../config/database.php';
require_once '../includes/core_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'get_customers':
            $searchTerm = $_GET['search'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            if ($searchTerm) {
                $customers = searchCustomers($searchTerm);
            } else {
                $query = "SELECT * FROM Customers WHERE Status = 'Active' ORDER BY FirstName, LastName LIMIT ? OFFSET ?";
                $customers = dbFetchAll($query, [$limit, $offset]);
            }
            
            jsonResponse(['success' => true, 'data' => $customers]);
            break;

        case 'add_customer':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $customerId = addCustomer($data);
            
            jsonResponse(['success' => true, 'message' => 'Customer added', 'customer_id' => $customerId]);
            break;

        case 'get_customer':
            $customerId = $_GET['customer_id'] ?? null;
            if (!$customerId) throw new Exception('Customer ID required');
            
            $customer = getCustomer($customerId);
            if (!$customer) {
                throw new Exception('Customer not found');
            }
            
            // Get customer statistics
            $query = "SELECT COUNT(*) as total_orders, SUM(TotalAmount) as lifetime_value FROM Sales WHERE CustomerID = ?";
            $stats = dbFetchOne($query, [$customerId]);
            
            $customer = array_merge($customer, $stats);
            
            jsonResponse(['success' => true, 'data' => $customer]);
            break;

        case 'update_customer':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $customerId = $data['customer_id'];
            
            $query = "UPDATE Customers SET FirstName = ?, LastName = ?, Email = ?, Phone = ?, Address = ? WHERE CustomerID = ?";
            dbUpdate($query, [$data['first_name'], $data['last_name'], $data['email'], 
                            $data['phone'], $data['address'], $customerId]);
            
            jsonResponse(['success' => true, 'message' => 'Customer updated']);
            break;

        case 'get_customer_orders':
            $customerId = $_GET['customer_id'] ?? null;
            if (!$customerId) throw new Exception('Customer ID required');
            
            $query = "SELECT * FROM Sales WHERE CustomerID = ? ORDER BY SaleDate DESC";
            $orders = dbFetchAll($query, [$customerId]);
            
            jsonResponse(['success' => true, 'data' => $orders]);
            break;

        case 'get_loyalty_info':
            $customerId = $_GET['customer_id'] ?? null;
            if (!$customerId) throw new Exception('Customer ID required');
            
            $customer = getCustomer($customerId);
            if (!$customer) {
                throw new Exception('Customer not found');
            }
            
            $points = $customer['LoyaltyPoints'] ?? 0;
            
            // Determine tier
            if ($points >= 20000) {
                $tier = 'Platinum';
                $bonus = 'VIP benefits';
            } elseif ($points >= 5000) {
                $tier = 'Gold';
                $bonus = '+20% bonus';
            } elseif ($points >= 1000) {
                $tier = 'Silver';
                $bonus = '+10% bonus';
            } else {
                $tier = 'Bronze';
                $bonus = 'Base rewards';
            }
            
            $loyaltyData = [
                'points' => $points,
                'tier' => $tier,
                'bonus' => $bonus,
                'points_to_next_tier' => $this->getPointsToNextTier($points),
                'rewards_available' => floor($points / 500) // 500 points = 1 reward
            ];
            
            jsonResponse(['success' => true, 'data' => $loyaltyData]);
            break;

        case 'redeem_reward':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $customerId = $data['customer_id'];
            $rewardPoints = 500; // Per reward
            
            $customer = getCustomer($customerId);
            if (!$customer || $customer['LoyaltyPoints'] < $rewardPoints) {
                throw new Exception('Insufficient loyalty points');
            }
            
            updateLoyaltyPoints($customerId, $rewardPoints, 'subtract');
            
            jsonResponse(['success' => true, 'message' => 'Reward redeemed']);
            break;

        case 'get_support_tickets':
            $customerId = $_GET['customer_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $query = "SELECT st.* FROM SupportTickets st WHERE 1=1";
            $params = [];
            
            if ($customerId) {
                $query .= " AND st.CustomerID = ?";
                $params[] = $customerId;
            }
            
            if ($status) {
                $query .= " AND st.Status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY st.CreatedDate DESC";
            $tickets = dbFetchAll($query, $params);
            
            jsonResponse(['success' => true, 'data' => $tickets]);
            break;

        case 'create_support_ticket':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $ticketId = createSupportTicket($data['customer_id'], $_SESSION['store_id'], 
                                          $data['subject'], $data['description'], 
                                          $data['priority'] ?? 'Medium');
            
            jsonResponse(['success' => true, 'message' => 'Support ticket created', 'ticket_id' => $ticketId]);
            break;

        case 'update_support_ticket':
            if ($method !== 'POST' || !hasPermission('Support')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            updateSupportTicket($data['ticket_id'], $data['status'], $data['resolution'] ?? null);
            
            jsonResponse(['success' => true, 'message' => 'Support ticket updated']);
            break;

        case 'get_ticket_details':
            $ticketId = $_GET['ticket_id'] ?? null;
            if (!$ticketId) throw new Exception('Ticket ID required');
            
            $ticket = dbFetchOne("SELECT * FROM SupportTickets WHERE TicketID = ?", [$ticketId]);
            
            jsonResponse(['success' => true, 'data' => $ticket]);
            break;

        case 'get_customer_statistics':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            
            $query = "SELECT 
                        COUNT(DISTINCT c.CustomerID) as total_customers,
                        COUNT(DISTINCT s.SaleID) as total_orders,
                        SUM(s.TotalAmount) as total_spent,
                        COUNT(DISTINCT st.TicketID) as open_tickets
                     FROM Customers c
                     LEFT JOIN Sales s ON c.CustomerID = s.CustomerID
                     LEFT JOIN SupportTickets st ON c.CustomerID = st.CustomerID AND st.Status != 'Closed'
                     WHERE s.StoreID = ? OR st.StoreID = ?";
            
            $stats = dbFetchOne($query, [$storeId, $storeId]);
            jsonResponse(['success' => true, 'data' => $stats]);
            break;

        case 'get_loyalty_members':
            $tier = $_GET['tier'] ?? null;
            
            $query = "SELECT * FROM Customers WHERE LoyaltyPoints > 0";
            $params = [];
            
            if ($tier) {
                $pointRanges = [
                    'Bronze' => [0, 999],
                    'Silver' => [1000, 4999],
                    'Gold' => [5000, 19999],
                    'Platinum' => [20000, 999999]
                ];
                
                if (isset($pointRanges[$tier])) {
                    $query .= " AND LoyaltyPoints BETWEEN ? AND ?";
                    $params[] = $pointRanges[$tier][0];
                    $params[] = $pointRanges[$tier][1];
                }
            }
            
            $query .= " ORDER BY LoyaltyPoints DESC";
            $members = dbFetchAll($query, $params);
            
            jsonResponse(['success' => true, 'data' => $members]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Customer API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}

function getPointsToNextTier($currentPoints) {
    if ($currentPoints >= 20000) return 0;
    elseif ($currentPoints >= 5000) return 20000 - $currentPoints;
    elseif ($currentPoints >= 1000) return 5000 - $currentPoints;
    else return 1000 - $currentPoints;
}
?>
