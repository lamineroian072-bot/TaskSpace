// js/app.js
let analyticsChart = null;
let cart = [];
let allProducts = [];

document.addEventListener('DOMContentLoaded', () => {
    feather.replace();
    const printTime = document.getElementById('printTimestamp');
    if (printTime) printTime.innerText = 'Generated on: ' + new Date().toLocaleString();
    loadStoreData();
});

function switchView(viewName) {
    if (viewName === 'admin') {
        // Verify authentication before opening admin dashboard
        fetch('api.php?action=check_auth')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    document.getElementById('storefrontView').style.display = 'none';
                    document.getElementById('adminView').style.display = 'block';
                    updateActiveTab(1);
                } else {
                    window.location.href = 'login.php';
                }
            });
    } else {
        document.getElementById('storefrontView').style.display = 'block';
        document.getElementById('adminView').style.display = 'none';
        updateActiveTab(0);
    }
}

function updateActiveTab(index) {
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(btn => btn.classList.remove('active'));
    if (tabs[index]) tabs[index].classList.add('active');
}

async function loadStoreData() {
    try {
        const response = await fetch('api.php?action=read_products');
        const result = await response.json();

        if (result.status === 'success') {
            allProducts = result.data;
            renderStorefront(allProducts);
            renderAdminInventory(allProducts);
            renderOrdersTable(result.orders);
            renderAnalytics(result.analytics);
        }
    } catch (error) {
        console.error('Error fetching store data:', error);
    }
}

function renderStorefront(products) {
    const grid = document.getElementById('catalogGrid');
    if (!products || products.length === 0) {
        grid.innerHTML = '<p style="color: var(--text-secondary);">No iPhones currently available in stock.</p>';
        return;
    }

    grid.innerHTML = products.map(item => `
        <div class="product-card">
            <div class="product-image-wrapper">
                <span class="product-tag ${item.condition_type === 'Brand New' ? 'tag-new' : 'tag-secondhand'}">
                    ${item.condition_type}
                </span>
                <img src="${item.image_url || 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&auto=format&fit=crop'}" alt="${item.model}" onerror="this.src='https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&auto=format&fit=crop';">
            </div>
            <div class="product-details">
                <div>
                    <div class="product-title">${item.model}</div>
                    <div class="product-spec">Storage: ${item.storage} | Available: ${item.stock}</div>
                </div>
                <div>
                    <div class="product-price">₱${parseFloat(item.price).toLocaleString('en-PH', {minimumFractionDigits: 2})}</div>
                    <button class="btn btn-full" onclick="addToCart(${item.id})">
                        <i data-feather="shopping-bag"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    feather.replace();
}

function addToCart(productId) {
    const product = allProducts.find(p => p.id === productId);
    if (!product) return;

    const existing = cart.find(item => item.id === productId);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ ...product, qty: 1 });
    }

    updateCartUI();
    toggleCart(true);
}

function updateCartUI() {
    const cartList = document.getElementById('cartItemsList');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotalAmount');

    const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    cartCount.innerText = totalQty;
    cartTotal.innerText = '₱' + totalPrice.toLocaleString('en-PH', {minimumFractionDigits: 2});

    if (cart.length === 0) {
        cartList.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Your shopping cart is empty.</p>';
        return;
    }

    cartList.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
            <img class="cart-item-img" src="${item.image_url}" alt="${item.model}">
            <div style="flex-grow: 1;">
                <strong>${item.model}</strong> (${item.condition_type})
                <div style="font-size: 0.8rem; color: var(--text-secondary);">₱${parseFloat(item.price).toLocaleString('en-PH')} x ${item.qty}</div>
            </div>
            <button class="btn-icon" onclick="removeFromCart(${index})">
                <i data-feather="x"></i>
            </button>
        </div>
    `).join('');

    feather.replace();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function toggleCart(open = null) {
    const drawer = document.getElementById('cartDrawer');
    if (open === true) {
        drawer.classList.add('open');
    } else if (open === false) {
        drawer.classList.remove('open');
    } else {
        drawer.classList.toggle('open');
    }
}

function toggleFulfillmentUI() {
    const type = document.getElementById('fulfillment_type').value;
    const addrGroup = document.getElementById('addressGroup');
    addrGroup.style.display = (type === 'In-Store Pickup') ? 'none' : 'block';
}

async function handleCheckout(event) {
    event.preventDefault();

    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    const formData = new FormData(event.target);
    const summary = cart.map(i => `${i.model} (${i.condition_type}) x${i.qty}`).join(', ');
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    formData.append('product_summary', summary);
    formData.append('total_amount', total);

    try {
        const response = await fetch('api.php?action=checkout', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.status === 'success') {
            alert('🎉 ' + result.message);
            cart = [];
            updateCartUI();
            toggleCart(false);
            event.target.reset();
            loadStoreData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Checkout failed:', error);
    }
}

function renderAdminInventory(products) {
    const tbody = document.getElementById('inventoryTableBody');
    if (!tbody) return;

    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-secondary);">No inventory records found.</td></tr>';
        return;
    }

    tbody.innerHTML = products.map(item => `
        <tr>
            <td>#${item.id}</td>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="${item.image_url}" style="width: 35px; height: 35px; border-radius: 6px; object-fit: cover;">
                    <strong>${item.model}</strong>
                </div>
            </td>
            <td>${item.condition_type}</td>
            <td>${item.storage}</td>
            <td>₱${parseFloat(item.price).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            <td>${item.stock}</td>
            <td>
                <span style="color: ${item.status === 'Out of Stock' ? 'var(--danger)' : item.status === 'Low Stock' ? '#f59e0b' : 'var(--success)'}; font-weight: 600;">
                    ${item.status}
                </span>
            </td>
            <td>
                <button class="btn-icon" onclick="deleteProduct(${item.id})">
                    <i data-feather="trash-2"></i>
                </button>
            </td>
        </tr>
    `).join('');

    feather.replace();
}

function renderOrdersTable(orders) {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-secondary);">No customer orders placed yet.</td></tr>';
        return;
    }

    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>#ORD-${order.id}</td>
            <td><strong>${order.customer_name}</strong><br><small style="color: var(--text-secondary);">${order.customer_phone}</small></td>
            <td><span class="product-tag ${order.fulfillment_type === 'Online Delivery' ? 'tag-new' : 'tag-secondhand'}" style="position: static;">${order.fulfillment_type}</span></td>
            <td>${order.product_summary}</td>
            <td>₱${parseFloat(order.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            <td>${order.created_at}</td>
        </tr>
    `).join('');
}

function renderAnalytics(analytics) {
    if (!analytics || !document.getElementById('statTotalProducts')) return;

    document.getElementById('statTotalProducts').innerText = analytics.total_products || 0;
    document.getElementById('statBrandNew').innerText = analytics.brand_new_count || 0;
    document.getElementById('statSecondhand').innerText = analytics.secondhand_count || 0;

    const ctx = document.getElementById('analyticsChart');
    if (!ctx) return;

    if (analyticsChart) analyticsChart.destroy();

    analyticsChart = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Brand New', 'Secondhand'],
            datasets: [{
                data: [analytics.brand_new_count || 0, analytics.secondhand_count || 0],
                backgroundColor: ['#10b981', '#f59e0b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
        }
    });
}

async function handleAddProduct(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    try {
        const response = await fetch('api.php?action=create_product', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.status === 'success') {
            event.target.reset();
            loadStoreData();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Failed to add product:', error);
    }
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to remove this item from inventory?')) return;

    const formData = new FormData();
    formData.append('id', id);

    try {
        const response = await fetch('api.php?action=delete_product', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.status === 'success') {
            loadStoreData();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Delete failed:', error);
    }
}