<script setup>
import { onMounted, ref } from "vue";
import { loadStripe } from "@stripe/stripe-js";
import { router } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'

import topImage from "~/images/top.jpg";
import Swal from 'sweetalert2'

const stripePublicKey = import.meta.env.VITE_STRIPE_PUBLIC_KEY;
let stripe, card;

const props = defineProps({
    ticket: Object,
    amount: Number,
    parentUrl: String,
    repairItems: Object
});

const form = ref({
    ticket_id: props.ticket.ticket_id,
    ticket_num: props.ticket.ticket_num,
    name: props.ticket.name,
    email: props.ticket.email,
    phone: props.ticket.phone,
    amount: props.amount,
});

const errors = ref({});
const loading = ref(false);

onMounted(async () => {
    stripe = await loadStripe(stripePublicKey);
    const elements = stripe.elements();
    card = elements.create("card", {
        hidePostalCode: true, style: {
            base: {
                fontSize: '16px',
                color: '#212529',
                '::placeholder': { color: '#6c757d' },
                fontFamily: 'inherit',
            },
        },
    });
    card.mount("#card-element");
    card.on('change', event => {
        if (event.error) {
            errors.value.card = event.error.message;
        } else {
            errors.value.card = null;
        }
    });
});

// ---- FORM VALIDATION ----
function validateForm() {
    const newErrors = {};

    if (!form.value.name.trim()) {
        newErrors.name = "Full name is required.";
    } else if (form.value.name.length < 3) {
        newErrors.name = "Name must be at least 3 characters.";
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!form.value.email.trim()) {
        newErrors.email = "Email is required.";
    } else if (!emailRegex.test(form.value.email)) {
        newErrors.email = "Enter a valid email address.";
    }

    const phoneRegex = /^[0-9+\-()\s]{6,15}$/;
    if (!form.value.phone.trim()) {
        newErrors.phone = "Phone number is required.";
    } else if (!phoneRegex.test(form.value.phone)) {
        newErrors.phone = "Enter a valid phone number.";
    }

    const amountValue = parseFloat(form.value.amount);

    if (isNaN(amountValue) || amountValue <= 0) {
        newErrors.amount =
            "This processor does not accept zero-dollar authorization for this card type.";
    }

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
}

const handleFormSubmit = async () => {
    if (!validateForm()) return;

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    if (!emailPattern.test(form.value.email)) {
        alert('Please enter a valid email address.')
        return
    }

    loading.value = true;
    errors.value.card = null;

    try {
        const { error: cardError, paymentMethod } = await stripe.createPaymentMethod({
            type: 'card',
            card: card,
            billing_details: {
                name: form.value.name,
                email: form.value.email,
                phone: form.value.phone,
            },
        });

        if (cardError) {
            errors.value.card = cardError.message;
            loading.value = false;
            return;
        }

        // ✅ Step 1: Create or update customer
        const customerResponse = await axios.post(route('stripe.create-customer'), {
            ticket_num: form.value.ticket_num,
            ticket_id: form.value.ticket_id,
            amount: form.value.amount * 100,
            name: `Ticket ${props.ticket.ticket_num}`,
            email: form.value.email,
            phone: form.value.phone,
            address: props.ticket.address,
            shipping: props.ticket.shipping,
        });

        const customer = customerResponse.data
        if (customer.error) throw new Error(customer.error);

        // ✅ Step 2: Create payment method via Stripe
        const paymentMethodResult = await stripe.createPaymentMethod({
            type: 'card',
            card: card,
            billing_details: {
                name: form.value.name,
                email: form.value.email,
                phone: form.value.phone,
            },
        });

        if (paymentMethodResult.error) throw new Error(paymentMethodResult.error.message);

        // ✅ Step 3: Attach payment method to customer
        const attachResponse = await axios.post(route('stripe.attach-payment-method-to-customer'), {
            customer_id: customer.id,
            payment_method_id: paymentMethodResult.paymentMethod.id,
        });

        if (attachResponse.data.error) throw new Error(attachResponse.data.error)

        // ✅ Step 4: Create payment intent
        const intentResponse = await axios.post(route('stripe.create-payment-intent'), {
            customer_id: customer.id,
            amount: form.value.amount,
            payment_method_id: paymentMethodResult.paymentMethod.id,
        });

        const paymentIntent = intentResponse.data
        if (paymentIntent.error) throw new Error(paymentIntent.error)

        // ✅ Step 5: Confirm payment
        const confirmResult = await stripe.confirmCardPayment(paymentIntent.client_secret, {
            payment_method: paymentMethodResult.paymentMethod.id,
        });

        if (confirmResult.error) {
            router.get(`/ticket/${form.value.ticket_id}/payment-cancel`)
        } else {
            window.location = `${import.meta.env.VITE_OHD_BASE_URL}/_tmp/stripe-success.php?payment_intent=${paymentIntent.id}&payment_method=${confirmResult.paymentIntent.payment_method}&client_secret=${confirmResult.paymentIntent.client_secret}&customer=${customer.id}&ticket_id=${form.value.ticket_id}`
        }
    } catch (err) {
        Swal.fire({
            title: 'Opps!',
            text: err.message || 'Something went wrong while processing payment.',
            icon: 'error',
        });
    } finally {
        loading.value = false;
    }
};

const handleCancel = async () => {
    const result = await Swal.fire({
        title: 'Cancel Payment?',
        text: 'Are you sure you want to cancel the payment?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it',
        cancelButtonText: 'No, keep going',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true,
    })

    if (result.isConfirmed) {
        form.value = { name: '', email: '', phone: '' }
        card.clear()

        await Swal.fire({
            icon: 'info',
            title: 'Payment Cancelled',
            text: 'Your payment has been cancelled successfully.',
            timer: 2000,
            showConfirmButton: false
        })

        window.close()
    }
};
</script>

<template>
    <Head title="Secure Payment" />
    <div class="checkout-wrapper">
        <div class="checkout-card row shadow-lg rounded-4 overflow-hidden">
            
            <!-- LEFT — Order Summary -->
            <div class="col-lg-6 col-md-12 summary-section p-5">
                <div class="summary-content">
                    <img :src="topImage" alt="Logo" class="img-fluid mb-4" />
                    <h3 class="fw-semibold mb-4 text-dark">🧾 Order Summary</h3>

                    <table class="table table-borderless align-middle mb-4">
                        <thead class="text-muted border-bottom">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in repairItems" :key="i">
                                <td>{{ item.item }}</td>
                                <td class="text-end">${{ item.maximum_charge }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="my-3" />

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold fs-5 text-dark">Total</span>
                        <span class="fw-bold fs-4 text-primary">${{ form.amount }}</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT — Payment Form -->
            <div class="col-lg-6 col-md-12 payment-section p-5 d-flex align-items-center">
                <div class="payment-form w-100">
                    <h3 class="fw-semibold mb-4 text-dark text-center">🔒 Secure Payment</h3>

                    <form @submit.prevent="handleFormSubmit" novalidate>
                        <!-- Name -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input v-model.trim="form.name" type="text" class="form-control form-control-lg"
                                :class="{ 'is-invalid': errors.name }" />
                            <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input v-model.trim="form.email" type="email" class="form-control form-control-lg"
                                :class="{ 'is-invalid': errors.email }" />
                            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input v-model.trim="form.phone" type="text" class="form-control form-control-lg"
                                :class="{ 'is-invalid': errors.phone }" />
                            <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone }}</div>
                        </div>

                        <!-- Card -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Card Details</label>
                            <div id="card-element"
                                class="form-control p-2"
                                :class="{ 'is-invalid': errors.card || errors.amount }"></div>
                            <div v-if="errors.card" class="text-danger mt-1 small">{{ errors.card }}</div>
                            <div v-if="errors.amount" class="text-danger mt-1 small">{{ errors.amount }}</div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" @click="handleCancel" class="btn btn-light w-50 py-2">
                                Cancel
                            </button>
                            <button type="submit"
                                class="btn btn-gradient-primary w-50 py-2 d-flex align-items-center justify-content-center"
                                :disabled="loading">
                                <div v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></div>
                                <span>{{ loading ? "Processing..." : "Confirm Payment" }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ----------- Layout Base ----------- */
.checkout-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8faff 0%, #eef3ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.checkout-card {
    background: #fff;
    border-radius: 1.5rem;
    max-width: 1100px;
    width: 100%;
}

.summary-section {
    background-color: #f9fbff;
    border-right: 1px solid #e5e9f0;
}

.payment-section {
    background-color: #ffffff;
}

/* ----------- Buttons ----------- */
.btn-gradient-primary {
    background: linear-gradient(135deg, #6d5dfc, #4c4eff);
    color: #fff;
    border: none;
    transition: all 0.3s ease;
}

.btn-gradient-primary:hover {
    background: linear-gradient(135deg, #5b4def, #3f41ff);
    box-shadow: 0 4px 12px rgba(99, 91, 255, 0.3);
}

.btn-light {
    background: #f0f2f7;
    border: none;
    transition: all 0.25s ease;
}

.btn-light:hover {
    background: #e2e6ef;
}

/* ----------- Typography ----------- */
.form-label {
    font-size: 0.95rem;
    color: #374151;
}

.table th {
    font-weight: 600;
}

.text-primary {
    color: #4c4eff !important;
}

/* ----------- Inputs ----------- */
.form-control {
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    transition: all 0.25s ease;
    font-size: 1rem;
}

.form-control:focus {
    border-color: #6d5dfc;
    box-shadow: 0 0 0 0.2rem rgba(99, 91, 255, 0.2);
}

/* ----------- Responsive ----------- */
@media (max-width: 768px) {
    .summary-section {
        border-right: none;
        border-bottom: 1px solid #e5e9f0;
    }

    .checkout-card {
        border-radius: 1rem;
    }
}
</style>
