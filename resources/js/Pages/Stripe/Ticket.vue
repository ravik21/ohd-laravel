<template>
    <div class="checkout-wrapper">
        <div class="checkout-container row">
            <img :src="topImage" alt="Logo" class="img-fluid" />
            <!-- LEFT COLUMN — ORDER SUMMARY -->
            <div class="col-lg-6 col-md-12 summary-section d-flex flex-column justify-content-top">
                <div class="summary-content mx-auto w-100 px-2 px-md-5 py-4">
                    <h4 class="fw-bold mb-4 text-center text-md-start">Order Summary</h4>

                    <table class="table table-borderless align-middle">
                        <thead>
                            <tr>
                                <th>Items</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) of repairItems" :key="i">
                                <td>{{ item.item }}</td>
                                <td>${{ item.maximum_charge }}</td>
                            </tr>
                        </tbody>

                    </table>

                    <hr class="my-4" />

                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">Total</span>
                        <span class="fw-bold fs-5">${{ form.amount }}</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN — PAYMENT FORM -->
            <div class="col-lg-6 col-md-12 payment-section d-flex align-items-center justify-content-center">
                <div class="payment-form w-100 px-2 px-md-5 py-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">Secure Payment</h4>
                    </div>

                    <form @submit.prevent="handleFormSubmit" novalidate>
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="billing-name" class="form-label fw-semibold">Full Name</label>
                            <input v-model.trim="form.name" type="text" id="billing-name" class="form-control"
                                :class="{ 'is-invalid': errors.name }" />
                            <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="billing-email" class="form-label fw-semibold">Email</label>
                            <input v-model.trim="form.email" type="email" id="billing-email" class="form-control"
                                :class="{ 'is-invalid': errors.email }" />
                            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="billing-phone" class="form-label fw-semibold">Phone</label>
                            <input v-model.trim="form.phone" type="text" id="billing-phone" class="form-control"
                                :class="{ 'is-invalid': errors.phone }" />
                            <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone }}</div>
                        </div>

                        <!-- Card -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Card Details</label>
                            <div id="card-element" class="form-control p-2"
                                :class="{ 'is-invalid': errors.card || errors.amount }">
                            </div>
                            <div v-if="errors.card" class="text-danger mt-1 small">{{ errors.card }}</div>
                            <div v-if="errors.amount" class="text-danger mt-1 small">{{ errors.amount }}</div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" @click="handleCancel" class="btn btn-outline-secondary w-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="btn btn-primary w-50 d-flex align-items-center justify-content-center"
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

<script setup>
import { onMounted, ref } from "vue";
import { loadStripe } from "@stripe/stripe-js";
import { router } from '@inertiajs/vue3'

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
        const customerResponse = await axios.post(route('ticket.stripe.create-customer'), {
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
            card: card.value,
            billing_details: { name: form.value.name },
        });

        if (paymentMethodResult.error) throw new Error(paymentMethodResult.error.message);

        // ✅ Step 3: Attach payment method to customer
        const attachResponse = await axios.post(route('ticket.stripe.attach-payment-method-to-customer'), {
            customerId: customer.id,
            paymentMethodId: paymentMethodResult.paymentMethod.id,
        });

        if (attachResponse.data.error) throw new Error(attachResponse.data.error)

        // ✅ Step 4: Create payment intent
        const intentResponse = await axios.post(route('ticket.stripe.create-payment-intent'), {
            customer_id: customer.id,
            amount: amountValue,
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
            router.get(`/ticket/${form.value.ticket_id}/payment-success`, {
                payment_intent: paymentIntent.id,
                customer: customer.id,
            })
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

<style scoped>
.checkout-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.checkout-container {
    width: 100%;
    min-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-wrap: wrap;
}

.summary-section {
    background-color: #f8faff;
}

.payment-section {
    background-color: #ffffff;
}

.btn-primary {
    background: #635bff;
    border-color: #635bff;
    transition: all 0.25s ease;
}

.btn-primary:hover {
    background: #5148e5;
    border-color: #5148e5;
}

.col-lg-6+.col-lg-6 {
    border-left: 1px solid #e0e6ed;
}

/* ------------------- Responsive Design ------------------- */
@media (max-width: 1200px) {
    .checkout-container {
        max-width: 950px;
    }

    .col-lg-6+.col-lg-6 {
        border-left: 1px solid #e0e6ed;
    }
}

@media (max-width: 992px) {
    .summary-section {
        padding: 3rem 2rem;
    }

    .payment-section {
        padding: 3rem 2rem;
    }
}

@media (max-width: 768px) {
    .checkout-container {
        flex-direction: column;
        border-radius: 0;
        min-height: 100vh;
    }

    .summary-section {
        border-bottom: 1px solid #e0e6ed;
        padding: 2.5rem 1.5rem;
    }

    .payment-section {
        padding: 2.5rem 1.5rem;
    }

    .summary-content {
        max-width: 100%;
    }

    .btn {
        font-size: 0.95rem;
    }

    .col-lg-6+.col-lg-6 {
        border-left: 0px;
    }
}

@media (max-width: 480px) {

    .summary-section,
    .payment-section {
        padding: 1.8rem 1.2rem;
    }

    .checkout-container {
        min-height: auto;
        box-shadow: none;
    }

    .fw-bold.fs-5 {
        font-size: 1.1rem;
    }
}
</style>