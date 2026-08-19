<?php
require_once __DIR__ . '/config/db.php';

function getSalesSummary($pdo, $interval, $method) {
    $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE payment_method = ?";
    if ($interval === 'day') $sql .= " AND DATE(created_at) = CURDATE()";
    if ($interval === 'week') $sql .= " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    if ($interval === 'month') $sql .= " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$method]);
    return $stmt->fetch()['total'];
}

$dailyCash = getSalesSummary($pdo, 'day', 'Cash');
$dailyGCash = getSalesSummary($pdo, 'day', 'GCash');
$weeklyCash = getSalesSummary($pdo, 'week', 'Cash');
$weeklyGCash = getSalesSummary($pdo, 'week', 'GCash');
$monthlyCash = getSalesSummary($pdo, 'month', 'Cash');
$monthlyGCash = getSalesSummary($pdo, 'month', 'GCash');

$items = $pdo->query("SELECT * FROM items ORDER BY name ASC")->fetchAll();
$sales = $pdo->query("SELECT * FROM sales ORDER BY id DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - POS System</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Side Navbar -->
        <aside class="w-64 bg-slate-900 text-white flex flex-col no-print">
            <div class="p-5 font-bold text-xl border-b border-slate-700">POS Admin</div>
            <nav class="flex-1 p-4 space-y-2">
                <button onclick="switchTab('home')" class="w-full text-left py-2.5 px-4 rounded hover:bg-slate-800 font-medium transition">Home</button>
                <button onclick="switchTab('reprint')" class="w-full text-left py-2.5 px-4 rounded hover:bg-slate-800 font-medium transition">Reprint Receipt</button>
                <button onclick="triggerPrintReport()" class="w-full text-left py-2.5 px-4 rounded hover:bg-slate-800 font-medium text-emerald-400 transition">Print Report</button>
                <a href="cashier.php" class="block w-full text-left py-2.5 px-4 rounded hover:bg-slate-800 font-medium text-blue-400 mt-6">Open Cashier Terminal</a>
            </nav>
        </aside>

        <!-- Main Content View -->
        <main class="flex-1 overflow-y-auto p-6" id="printable-area">
            
            <!-- HOME SECTION (DEFAULT) -->
            <div id="tab-home" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold">Analytics & Inventory Overview</h1>
                    <span class="text-sm text-gray-500">Date: <?= date('F j, Y') ?></span>
                </div>

                <!-- Sales Analytics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Daily Sales -->
                    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Today's Sales</h3>
                        <div class="mt-3 text-base text-gray-700">Cash: <span class="font-bold">₱<?= number_format($dailyCash, 2) ?></span></div>
                        <div class="text-base text-blue-600">GCash: <span class="font-bold">₱<?= number_format($dailyGCash, 2) ?></span></div>
                        <div class="mt-2 pt-2 border-t text-lg font-extrabold text-gray-900">Total: ₱<?= number_format($dailyCash + $dailyGCash, 2) ?></div>
                    </div>

                    <!-- Weekly Sales -->
                    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">This Week's Sales</h3>
                        <div class="mt-3 text-base text-gray-700">Cash: <span class="font-bold">₱<?= number_format($weeklyCash, 2) ?></span></div>
                        <div class="text-base text-blue-600">GCash: <span class="font-bold">₱<?= number_format($weeklyGCash, 2) ?></span></div>
                        <div class="mt-2 pt-2 border-t text-lg font-extrabold text-gray-900">Total: ₱<?= number_format($weeklyCash + $weeklyGCash, 2) ?></div>
                    </div>

                    <!-- Monthly Sales -->
                    <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">This Month's Sales</h3>
                        <div class="mt-3 text-base text-gray-700">Cash: <span class="font-bold">₱<?= number_format($monthlyCash, 2) ?></span></div>
                        <div class="text-base text-blue-600">GCash: <span class="font-bold">₱<?= number_format($monthlyGCash, 2) ?></span></div>
                        <div class="mt-2 pt-2 border-t text-lg font-extrabold text-gray-900">Total: ₱<?= number_format($monthlyCash + $monthlyGCash, 2) ?></div>
                    </div>
                </div>

                <!-- Item Inventory Stock Counts -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <h2 class="text-xl font-bold mb-4">Item Inventory Stocks Count</h2>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-gray-600 text-sm">
                                <th class="p-3">Item ID</th>
                                <th class="p-3">Item Name</th>
                                <th class="p-3">Price</th>
                                <th class="p-3">Stock Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="p-3">#<?= $item['id'] ?></td>
                                    <td class="p-3 font-medium text-gray-900"><?= htmlspecialchars($item['name']) ?></td>
                                    <td class="p-3">₱<?= number_format($item['price'], 2) ?></td>
                                    <td class="p-3 font-bold <?= $item['stock'] < 10 ? 'text-red-500' : 'text-emerald-600' ?>">
                                        <?= $item['stock'] ?> units
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REPRINT RECEIPT SECTION -->
            <div id="tab-reprint" class="hidden space-y-6 no-print">
                <h1 class="text-2xl font-bold">Transaction Printed Receipts</h1>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-gray-600 text-sm">
                                <th class="p-3">Sale ID</th>
                                <th class="p-3">Date & Time</th>
                                <th class="p-3">Payment Method</th>
                                <th class="p-3">Total Amount</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td class="p-3">#<?= $sale['id'] ?></td>
                                    <td class="p-3 text-gray-600"><?= $sale['created_at'] ?></td>
                                    <td class="p-3 font-semibold"><?= $sale['payment_method'] ?></td>
                                    <td class="p-3 font-bold">₱<?= number_format($sale['total_amount'], 2) ?></td>
                                    <td class="p-3 text-right">
                                        <button onclick="reprintReceipt(<?= $sale['id'] ?>)" class="bg-slate-900 text-white px-3 py-1.5 rounded hover:bg-slate-700 text-sm">
                                            Reprint Receipt
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 no-print">
        <div class="bg-white p-6 rounded-lg max-w-sm w-full shadow-xl">
            <div id="receiptContent"></div>
            <div class="mt-4 flex gap-2">
                <button onclick="window.print()" class="flex-1 bg-emerald-600 text-white py-2 rounded font-bold">Print</button>
                <button onclick="closeModal()" class="flex-1 bg-gray-200 py-2 rounded">Close</button>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.getElementById('tab-home').classList.add('hidden');
            document.getElementById('tab-reprint').classList.add('hidden');
            document.getElementById('tab-' + tabName).classList.remove('hidden');
        }

        function triggerPrintReport() {
            switchTab('home');
            setTimeout(() => { window.print(); }, 200);
        }

        async function reprintReceipt(saleId) {
            const response = await fetch(`api/api.php?action=get_receipt&sale_id=${saleId}`);
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                let html = `<h2 class="text-xl font-bold text-center">POS RECEIPT</h2>`;
                html += `<p class="text-xs text-center text-gray-500 mb-4">Sale #${data[0].sale_id} | ${data[0].created_at}</p>`;
                html += `<div class="border-t border-b py-2 space-y-1 text-sm">`;
                data.forEach(item => {
                    html += `<div class="flex justify-between">
                        <span>${item.name} x${item.quantity}</span>
                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>`;
                });
                html += `</div>`;
                html += `<div class="text-right font-bold text-base mt-3">Total (${data[0].payment_method}): ₱${parseFloat(data[0].total_amount).toFixed(2)}</div>`;
                
                document.getElementById('receiptContent').innerHTML = html;
                document.getElementById('receiptModal').classList.remove('hidden');
                document.getElementById('receiptModal').classList.add('flex');
            }
        }

        function closeModal() {
            document.getElementById('receiptModal').classList.add('hidden');
            document.getElementById('receiptModal').classList.remove('flex');
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>