<?php
require_once __DIR__ . '/config/db.php';
$items = $pdo->query("SELECT * FROM items WHERE stock > 0 ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Terminal - POS System</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex flex-col md:flex-row h-screen">
        
        <!-- Left: Product Selection Catalog -->
        <div class="w-full md:w-2/3 p-5 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Cashier Terminal</h1>
                <a href="admin.php" class="bg-slate-900 text-white px-4 py-2 rounded text-sm font-medium">Admin Dashboard</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($items as $item): ?>
                    <button onclick="addToCart(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>', <?= $item['price'] ?>, <?= $item['stock'] ?>)" 
                            class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:border-emerald-500 text-left transition flex flex-col justify-between">
                        <div>
                            <div class="font-bold text-gray-800 line-clamp-1"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="text-sm text-emerald-600 font-bold mt-1">₱<?= number_format($item['price'], 2) ?></div>
                        </div>
                        <div class="text-xs text-gray-400 mt-2">Available: <?= $item['stock'] ?></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Cart & Checkout -->
        <div class="w-full md:w-1/3 bg-white border-l border-gray-200 p-5 flex flex-col justify-between shadow-xl">
            <div>
                <h2 class="text-xl font-bold border-b pb-3 mb-4">Current Checkout</h2>
                <div id="cartList" class="space-y-3 max-h-[50vh] overflow-y-auto pr-1"></div>
            </div>

            <div class="border-t pt-4">
                <div class="flex justify-between text-xl font-bold mb-4">
                    <span>Total Pay:</span>
                    <span id="cartTotal" class="text-emerald-600">₱0.00</span>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Payment Method</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="btnCash" onclick="setPayment('Cash')" class="border-2 py-2 rounded-lg font-bold border-emerald-500 bg-emerald-50 text-emerald-700">Cash</button>
                        <button type="button" id="btnGCash" onclick="setPayment('GCash')" class="border-2 py-2 rounded-lg font-bold border-gray-200 text-gray-600">GCash</button>
                    </div>
                </div>

                <button onclick="processCheckout()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-lg shadow-lg transition">
                    Complete Sale
                </button>
            </div>
        </div>

    </div>

    <script>
        let cart = [];
        let selectedPayment = 'Cash';

        function addToCart(id, name, price, maxStock) {
            let item = cart.find(i => i.id === id);
            if (item) {
                if (item.quantity < maxStock) {
                    item.quantity++;
                } else {
                    alert('Maximum stock limit reached for this item.');
                }
            } else {
                cart.push({ id, name, price, quantity: 1, maxStock });
            }
            renderCart();
        }

        function setPayment(method) {
            selectedPayment = method;
            document.getElementById('btnCash').className = method === 'Cash' ? 'border-2 py-2 rounded-lg font-bold border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-2 py-2 rounded-lg font-bold border-gray-200 text-gray-600';
            document.getElementById('btnGCash').className = method === 'GCash' ? 'border-2 py-2 rounded-lg font-bold border-blue-500 bg-blue-50 text-blue-700' : 'border-2 py-2 rounded-lg font-bold border-gray-200 text-gray-600';
        }

        function renderCart() {
            const list = document.getElementById('cartList');
            list.innerHTML = '';
            let total = 0;

            cart.forEach((item, index) => {
                total += item.price * item.quantity;
                list.innerHTML += `
                    <div class="flex justify-between items-center text-sm p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <div class="font-bold text-gray-800">${item.name}</div>
                            <div class="text-xs text-gray-500">₱${item.price.toFixed(2)}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="adjustQty(${index}, -1)" class="w-7 h-7 bg-white border border-gray-300 rounded font-bold text-gray-600">-</button>
                            <span class="font-bold w-4 text-center">${item.quantity}</span>
                            <button onclick="adjustQty(${index}, 1)" class="w-7 h-7 bg-white border border-gray-300 rounded font-bold text-gray-600">+</button>
                        </div>
                    </div>`;
            });

            document.getElementById('cartTotal').innerText = `₱${total.toFixed(2)}`;
        }

        function adjustQty(index, delta) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) cart.splice(index, 1);
            renderCart();
        }

        async function processCheckout() {
            if (cart.length === 0) return alert('Cart is empty!');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            const res = await fetch('api/api.php?action=process_sale', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart, payment_method: selectedPayment, total_amount: total })
            });

            const result = await res.json();
            if (result.success) {
                alert(`Transaction Successful! Order #${result.sale_id}`);
                cart = [];
                renderCart();
                location.reload();
            } else {
                alert('Checkout failed: ' + result.message);
            }
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>