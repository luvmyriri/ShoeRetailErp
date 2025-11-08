<?php
require __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    // Sales trend (last 30 days)
    $sales_trend = dbFetchAll("SELECT DATE(SaleDate) AS date, SUM(TotalAmount) AS total FROM sales GROUP BY DATE(SaleDate) ORDER BY date DESC LIMIT 30");

    // Daily summary with richer metrics (last 30 days)
    $daily_summary = dbFetchAll("SELECT d.date,
        COUNT(s.SaleID) AS orders,
        IFNULL(SUM(s.TotalAmount),0) AS revenue,
        IFNULL(SUM(s.TaxAmount),0) AS tax,
        IFNULL(SUM(s.DiscountAmount),0) AS discounts,
        IFNULL(SUM(si.items_qty),0) AS items_sold
      FROM (
        SELECT DATE(SaleDate) AS date FROM sales GROUP BY DATE(SaleDate) ORDER BY date DESC LIMIT 30
      ) d
      LEFT JOIN sales s ON DATE(s.SaleDate) = d.date
      LEFT JOIN (
        SELECT SaleID, SUM(Quantity) AS items_qty FROM saledetails GROUP BY SaleID
      ) si ON si.SaleID = s.SaleID
      GROUP BY d.date
      ORDER BY d.date DESC");

    // Payment methods distribution
    $payment_methods = dbFetchAll("SELECT PaymentMethod AS method, SUM(TotalAmount) AS total FROM sales GROUP BY PaymentMethod ORDER BY total DESC");

    // Sales by store (last 30 days)
    $sales_by_store = dbFetchAll("SELECT COALESCE(st.StoreName,'Unknown') AS store, SUM(s.TotalAmount) AS total
                                  FROM sales s LEFT JOIN stores st ON s.StoreID = st.StoreID
                                  WHERE s.SaleDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                  GROUP BY store ORDER BY total DESC");

    // Top products (last 30 days)
    $top_products = dbFetchAll("SELECT CONCAT(p.Brand,' ',p.Model) AS product, SUM(sd.Quantity) AS qty, SUM(sd.Subtotal) AS revenue
                                FROM saledetails sd
                                JOIN sales s ON sd.SaleID = s.SaleID
                                JOIN products p ON sd.ProductID = p.ProductID
                                WHERE s.SaleDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                GROUP BY product ORDER BY revenue DESC LIMIT 10");

    echo json_encode([
        'sales_trend' => $sales_trend,
        'daily_summary' => $daily_summary,
        'payment_methods' => $payment_methods,
        'sales_by_store' => $sales_by_store,
'top_products' => $top_products,
        // Returns summary and recent list
        'returns_by_date' => dbFetchAll("SELECT DATE(ReturnDate) AS date, COUNT(*) AS returns_count, SUM(NetRefund) AS total_refund FROM returns GROUP BY DATE(ReturnDate) ORDER BY date DESC LIMIT 30"),
        'recent_returns' => dbFetchAll("SELECT ReturnID, SaleID, Reason, NetRefund, ReturnDate FROM returns ORDER BY ReturnDate DESC LIMIT 50")
    ]);
} catch (Exception $e) {
    logError('sales_report_data failed', ['error' => $e->getMessage()]);
    echo json_encode(['sales_trend' => [], 'daily_summary' => [], 'payment_methods' => [], 'sales_by_store' => [], 'top_products' => [], 'returns_by_date' => [], 'recent_returns' => []]);
}
?>
