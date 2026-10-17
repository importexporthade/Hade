/**
 * The cart lives in the customer's browser. Checking out opens WhatsApp with the order
 * written out, the same way orders reach the store today.
 */
const STORAGE_KEY = 'hade-cart';

export function formatPrice(amount) {
    return `S/ ${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function readCart() {
    try {
        const items = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');

        return Array.isArray(items) ? items.filter((item) => item && item.id && item.quantity > 0) : [];
    } catch {
        return [];
    }
}

function writeCart(items) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch {
        // Private browsing without storage: the cart still works until the page changes.
    }
}

let items = readCart();

function setItems(nextItems) {
    items = nextItems.filter((item) => item.quantity > 0);
    writeCart(items);
    render();
}

/**
 * The order as the store receives it: each product with its quantity, subtotal and link,
 * so the owner sees the photo of exactly what was chosen, then the total.
 */
export function orderMessage(cartItems) {
    const lines = cartItems.flatMap((item) => [
        `• ${item.quantity} x ${item.name} — ${formatPrice(item.price * item.quantity)}`,
        `  ${item.url}`,
    ]);
    const total = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const count = cartItems.reduce((sum, item) => sum + item.quantity, 0);

    return [
        'Hola, quiero comprar estos productos:',
        '',
        ...lines,
        '',
        `Total: ${formatPrice(total)} (${count} ${count === 1 ? 'producto' : 'productos'})`,
    ].join('\n');
}

function checkoutUrl(cartItems) {
    const number = document.body.dataset.whatsappNumber;

    return number ? `https://wa.me/${number}?text=${encodeURIComponent(orderMessage(cartItems))}` : null;
}

function render() {
    const count = items.reduce((sum, item) => sum + item.quantity, 0);
    const total = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

    document.querySelectorAll('[data-cart-count]').forEach((badge) => {
        badge.textContent = String(count);
    });

    const list = document.querySelector('[data-cart-items]');
    const template = document.getElementById('cart-item-template');

    if (!list || !template) {
        return;
    }

    list.replaceChildren(
        ...items.map((item) => {
            const row = template.content.firstElementChild.cloneNode(true);

            row.querySelector('[data-item-image]').src = item.image;
            row.querySelector('[data-item-name]').textContent = item.name;
            row.querySelector('[data-item-name]').href = item.url;
            row.querySelector('[data-item-price]').textContent = formatPrice(item.price);
            row.querySelector('[data-item-quantity]').textContent = String(item.quantity);
            row.querySelector('[data-item-decrease]').addEventListener('click', () => changeQuantity(item.id, -1));
            row.querySelector('[data-item-increase]').addEventListener('click', () => changeQuantity(item.id, 1));
            row.querySelector('[data-item-remove]').addEventListener('click', () => changeQuantity(item.id, -item.quantity));

            return row;
        }),
    );

    document.querySelector('[data-cart-empty]').hidden = items.length > 0;
    document.querySelector('[data-cart-footer]').hidden = items.length === 0;
    document.querySelector('[data-cart-total]').textContent = formatPrice(total);

    // The checkout link always carries the current cart, so tapping it opens WhatsApp at once
    const checkout = document.querySelector('[data-cart-checkout]');
    const url = checkoutUrl(items);

    checkout.hidden = url === null;
    checkout.href = url ?? '#';
}

/**
 * Tells the store what happened in the cart, for the manager's metrics. It never gets in
 * the customer's way: failures are ignored, and keepalive lets it finish while the page
 * moves on to WhatsApp.
 */
function sendMetric(endpoint, payload) {
    const url = document.querySelector(`meta[name="metrics-${endpoint}"]`)?.content;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!url || !token) {
        return;
    }

    fetch(url, {
        method: 'POST',
        keepalive: true,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(payload),
    }).catch(() => {});
}

function changeQuantity(id, delta) {
    const item = items.find((candidate) => candidate.id === id);

    if (!item) {
        return;
    }

    const quantity = Math.max(0, Math.min(99, item.quantity + delta));
    const applied = quantity - item.quantity;

    setItems(items.map((candidate) => (candidate.id === id ? { ...candidate, quantity } : candidate)));

    if (applied !== 0) {
        sendMetric('cart', { product_id: id, change: applied > 0 ? 'add' : 'remove', quantity: Math.abs(applied) });
    }
}

function addItem(product, quantity) {
    const existing = items.find((item) => item.id === product.id);
    const newQuantity = Math.min(99, (existing?.quantity ?? 0) + quantity);
    const added = newQuantity - (existing?.quantity ?? 0);

    setItems(
        existing
            ? items.map((item) => (item.id === product.id ? { ...product, quantity: newQuantity } : item))
            : [...items, { ...product, quantity: newQuantity }],
    );

    if (added > 0) {
        sendMetric('cart', { product_id: product.id, change: 'add', quantity: added });
    }
}

function openCart() {
    const drawer = document.querySelector('[data-cart]');

    drawer.hidden = false;
    document.body.style.overflow = 'hidden';
    drawer.querySelector('[data-cart-close]:not(.absolute)')?.focus();
}

function closeCart() {
    document.querySelector('[data-cart]').hidden = true;
    document.body.style.overflow = '';
}

export function initCart() {
    if (!document.querySelector('[data-cart]')) {
        return;
    }

    render();

    document.querySelectorAll('[data-cart-open]').forEach((button) => button.addEventListener('click', openCart));
    document.querySelectorAll('[data-cart-close]').forEach((button) => button.addEventListener('click', closeCart));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCart();
        }
    });

    document.querySelectorAll('[data-quantity-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('form').querySelector('[data-quantity]');

            input.value = String(Math.min(99, Math.max(1, Number(input.value || 1) + Number(button.dataset.quantityStep))));
        });
    });

    document.querySelectorAll('[data-add-to-cart]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const quantity = Math.min(99, Math.max(1, Number(form.querySelector('[data-quantity]')?.value || 1)));

            addItem(JSON.parse(form.dataset.addToCart), quantity);
            openCart();
        });
    });

    // The link opens WhatsApp on its own; this only records the order on the way out
    document.querySelector('[data-cart-checkout]').addEventListener('click', () => {
        sendMetric('checkout', { items: items.map((item) => ({ id: item.id, quantity: item.quantity })) });
    });
}
