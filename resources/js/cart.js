/**
 * The cart lives in the customer's browser. Checking out opens WhatsApp with the order
 * written out, the same way orders reach the store today.
 */
const STORAGE_KEY = 'hade-cart';
const CUSTOMER_KEY = 'hade-customer';

/** Each kind of shipping with the fields it asks for, in the order they go in the message. */
const SHIPPING = {
    lima: {
        label: 'Lima',
        fields: [
            ['district', 'Distrito'],
            ['address', 'Dirección'],
            ['reference', 'Referencia'],
        ],
    },
    provincia: { label: 'Provincia (fuera de Lima)', fields: [['agency', 'Agencia']] },
};

const CUSTOMER_FIELDS = ['first_name', 'last_name', 'dni', 'district', 'address', 'reference', 'agency'];

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

/** Which half of the drawer is showing: the products, or the shipping details. */
let step = 'items';

/**
 * The shipping details the customer typed, remembered so a returning customer does not
 * have to write them again.
 */
function readCustomer() {
    try {
        const customer = JSON.parse(localStorage.getItem(CUSTOMER_KEY) ?? '{}');

        return customer && typeof customer === 'object' ? customer : {};
    } catch {
        return {};
    }
}

function writeCustomer(customer) {
    try {
        localStorage.setItem(CUSTOMER_KEY, JSON.stringify(customer));
    } catch {
        // Without storage the details simply are not remembered.
    }
}

function customerForm() {
    return document.querySelector('[data-cart-customer]');
}

function customerFromForm() {
    const form = customerForm();

    if (!form) {
        return null;
    }

    // Read straight from the fields, so what was typed for the other shipping is remembered too
    const shipping = form.querySelector('[name="shipping"]:checked')?.value;
    const customer = { shipping: SHIPPING[shipping] ? shipping : 'lima' };

    CUSTOMER_FIELDS.forEach((name) => {
        customer[name] = String(form.elements[name]?.value ?? '').trim();
    });

    return customer;
}

function setItems(nextItems) {
    items = nextItems.filter((item) => item.quantity > 0);
    writeCart(items);
    render();
}

/**
 * The order as the store receives it: each product with its quantity, subtotal and link,
 * so the owner sees the photo of exactly what was chosen, then the total and who it ships to.
 */
export function orderMessage(cartItems, customer = null) {
    const lines = cartItems.flatMap((item) => [
        `• ${item.quantity} x ${item.name} — ${formatPrice(item.price * item.quantity)}`,
        `  ${item.url}`,
    ]);
    const total = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const count = cartItems.reduce((sum, item) => sum + item.quantity, 0);

    // The owner's opening message from "Datos de la tienda" starts the order when they wrote one
    return [
        document.body.dataset.whatsappGreeting || 'Hola, quiero comprar estos productos:',
        '',
        ...lines,
        '',
        `Total: ${formatPrice(total)} (${count} ${count === 1 ? 'producto' : 'productos'})`,
        ...customerLines(customer),
    ].join('\n');
}

function customerLines(customer) {
    if (!customer) {
        return [];
    }

    const shipping = SHIPPING[customer.shipping] ?? SHIPPING.lima;
    const name = [customer.first_name, customer.last_name].filter(Boolean).join(' ');

    return [
        '',
        'Mis datos:',
        ...(name ? [`Nombre: ${name}`] : []),
        ...(customer.dni ? [`DNI: ${customer.dni}`] : []),
        `Envío: ${shipping.label}`,
        ...shipping.fields.filter(([field]) => customer[field]).map(([field, label]) => `${label}: ${customer[field]}`),
    ];
}

function checkoutUrl(cartItems) {
    const number = document.body.dataset.whatsappNumber;

    return number ? `https://wa.me/${number}?text=${encodeURIComponent(orderMessage(cartItems, customerFromForm()))}` : null;
}

/**
 * The checkout link is a plain link, so the details are checked when it is tapped: a
 * missing field keeps WhatsApp closed and points the customer at what is left to fill.
 */
function customerIsComplete() {
    const form = customerForm();

    if (!form) {
        return true;
    }

    const missing = [...form.querySelectorAll('[required]')].filter((input) => !input.matches(':disabled') && input.value.trim() === '');

    missing.forEach((input) => input.setAttribute('aria-invalid', 'true'));
    missing[0]?.focus();

    return missing.length === 0;
}

/**
 * Shows only the fields of the chosen shipping: Lima asks for the address, the provinces only
 * for the agency. The hidden ones are disabled, so they are not required.
 */
function showShippingFields() {
    const shipping = customerFromForm().shipping;

    document.querySelectorAll('[data-cart-shipping-fields]').forEach((fieldset) => {
        const isChosen = fieldset.dataset.cartShippingFields === shipping;

        fieldset.hidden = !isChosen;
        fieldset.disabled = !isChosen;
    });
}

function initCustomerForm() {
    const form = customerForm();

    if (!form) {
        return;
    }

    const customer = readCustomer();

    CUSTOMER_FIELDS.forEach((name) => {
        form.elements[name].value = typeof customer[name] === 'string' ? customer[name] : '';
    });
    form.querySelector(`[name="shipping"][value="${SHIPPING[customer.shipping] ? customer.shipping : 'lima'}"]`).checked = true;
    showShippingFields();

    form.addEventListener('input', (event) => {
        event.target.removeAttribute('aria-invalid');
        writeCustomer(customerFromForm());
        showShippingFields();
        render();
    });
    form.addEventListener('submit', (event) => event.preventDefault());
}

function cartCount() {
    return items.reduce((sum, item) => sum + item.quantity, 0);
}

function render() {
    const count = cartCount();
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

    if (items.length === 0) {
        step = 'items';
    }

    document.querySelectorAll('[data-cart-step]').forEach((element) => {
        element.hidden = element.dataset.cartStep !== step;
    });
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

/** How long the added-to-cart notice stays up when nobody is touching it. */
const TOAST_DURATION = 4000;

/** How far the notice must be swiped sideways before it is dismissed. */
const TOAST_SWIPE_DISTANCE = 80;
const DRAWER_TRANSITION_DURATION = 500;

let toastTimer = null;
let drawerCloseTimer = null;

function cartToast() {
    return document.querySelector('[data-cart-toast]');
}

/**
 * Restarts the wiggle on every add, even when products are added in a row, and lets the
 * count pop in instead of jump when the cart was empty.
 */
function bumpCartIcon(wasEmpty) {
    document.querySelectorAll('[data-cart-icon]').forEach((icon) => {
        icon.classList.remove('cart-icon-bump', 'cart-icon-pop');
        void icon.offsetWidth;
        icon.classList.add(wasEmpty ? 'cart-icon-pop' : 'cart-icon-bump');
    });
}

function announce(message) {
    const announcer = document.querySelector('[data-cart-announcer]');

    if (!announcer) {
        return;
    }

    // Emptying it first makes screen readers repeat the message when the same product is added again
    announcer.textContent = '';
    setTimeout(() => {
        announcer.textContent = message;
    }, 50);
}

function scheduleToastHide() {
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, TOAST_DURATION);
}

function hideToast() {
    const toast = cartToast();

    clearTimeout(toastTimer);

    if (toast) {
        toast.dataset.open = 'false';
        toast.style.removeProperty('--toast-drag');
    }
}

/**
 * Shows what was just added. Adding more while it is up replaces its contents instead of
 * stacking another notice on top.
 */
function showToast(product) {
    const toast = cartToast();
    const quantity = items.find((item) => item.id === product.id)?.quantity ?? 0;

    announce(`${product.name} agregado a tu carrito`);

    if (!toast) {
        return;
    }

    toast.querySelector('[data-cart-toast-image]').src = product.image;
    toast.querySelector('[data-cart-toast-name]').textContent = product.name;
    toast.querySelector('[data-cart-toast-quantity]').textContent = `Tienes ${quantity} en tu carrito`;

    // On larger screens it hangs just under the sticky header, wherever the header sits right now
    const header = document.querySelector('header');

    if (header) {
        toast.style.setProperty('--cart-toast-top', `${Math.max(0, header.getBoundingClientRect().bottom) + 12}px`);
    }

    toast.dataset.open = 'true';
    scheduleToastHide();
}

function initCartToast() {
    const toast = cartToast();

    if (!toast) {
        return;
    }

    toast.querySelector('[data-cart-toast-close]').addEventListener('click', hideToast);

    // It waits while the customer is reading or about to tap it
    toast.addEventListener('pointerenter', () => clearTimeout(toastTimer));
    toast.addEventListener('pointerleave', () => toast.dataset.open === 'true' && scheduleToastHide());
    toast.addEventListener('focusin', () => clearTimeout(toastTimer));
    toast.addEventListener('focusout', () => toast.dataset.open === 'true' && scheduleToastHide());

    let swipeStart = null;

    toast.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'mouse') {
            swipeStart = event.clientX;
            clearTimeout(toastTimer);
        }
    });

    toast.addEventListener('pointermove', (event) => {
        if (swipeStart === null) {
            return;
        }

        toast.dataset.dragging = '';
        toast.style.setProperty('--toast-drag', `${event.clientX - swipeStart}px`);
    });

    const endSwipe = (event) => {
        if (swipeStart === null) {
            return;
        }

        const distance = Math.abs(event.clientX - swipeStart);

        swipeStart = null;
        delete toast.dataset.dragging;

        if (distance > TOAST_SWIPE_DISTANCE) {
            hideToast();
        } else {
            toast.style.removeProperty('--toast-drag');
            scheduleToastHide();
        }
    };

    toast.addEventListener('pointerup', endSwipe);
    toast.addEventListener('pointercancel', endSwipe);
}

function showStep(nextStep) {
    step = nextStep;
    render();

    document.querySelector('[data-cart-body]').scrollTop = 0;

    if (step === 'details') {
        const form = customerForm();

        ([...form.querySelectorAll('[required]')].find((input) => input.value.trim() === '') ?? form.elements.first_name).focus();
    } else {
        document.querySelector('[data-cart-next]').focus();
    }
}

function openCart() {
    const drawer = document.querySelector('[data-cart]');

    clearTimeout(drawerCloseTimer);
    hideToast();
    step = 'items';
    render();

    drawer.hidden = false;
    drawer.inert = false;
    drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    // Force the closed frame to render before starting the panel and backdrop transitions.
    drawer.getBoundingClientRect();
    drawer.dataset.open = 'true';
    drawer.querySelector('[data-cart-close]:not(.absolute)')?.focus();
}

function closeCart() {
    const drawer = document.querySelector('[data-cart]');

    if (drawer.hidden || drawer.dataset.open !== 'true') {
        return;
    }

    drawer.dataset.open = 'false';
    drawer.inert = true;

    const finishClose = () => {
        if (drawer.dataset.open === 'false') {
            drawer.hidden = true;
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            drawerCloseTimer = null;
        }
    };

    clearTimeout(drawerCloseTimer);

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        finishClose();
    } else {
        drawerCloseTimer = setTimeout(finishClose, DRAWER_TRANSITION_DURATION);
    }
}

export function initCart() {
    if (!document.querySelector('[data-cart]')) {
        return;
    }

    initCustomerForm();
    initCartToast();
    render();

    document.querySelectorAll('[data-cart-open]').forEach((button) => button.addEventListener('click', openCart));
    document.querySelectorAll('[data-cart-close]').forEach((button) => button.addEventListener('click', closeCart));
    document.querySelector('[data-cart-next]').addEventListener('click', () => showStep('details'));
    document.querySelector('[data-cart-back]').addEventListener('click', () => showStep('items'));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCart();
            hideToast();
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
            const product = JSON.parse(form.dataset.addToCart);
            const wasEmpty = cartCount() === 0;

            // Adding keeps the customer where they are; the drawer only opens from the cart icon or the notice
            addItem(product, quantity);
            bumpCartIcon(wasEmpty);
            showToast(product);
        });
    });

    // The link opens WhatsApp on its own; this only checks the details and records the order on the way out
    document.querySelector('[data-cart-checkout]').addEventListener('click', (event) => {
        if (!customerIsComplete()) {
            event.preventDefault();

            return;
        }

        sendMetric('checkout', { items: items.map((item) => ({ id: item.id, quantity: item.quantity })) });
    });
}
