<template>
    <div class="payment-success-wrapper d-flex justify-content-center align-items-center min-vh-100">
        <div class="card payment-card text-center shadow-lg border-0 p-4 p-md-5">
            <div class="card-body animate-fade-in">
                <!-- ✅ Payment Confirmed -->
                <div v-if="paymentIntentId">
                    <div class="icon-wrapper bg-success bg-opacity-10 text-success mb-4 mx-auto">
                        <i class="bi bi-check-circle-fill display-5"></i>
                    </div>
                    <h2 class="fw-bold text-success mb-2">Payment Confirmed</h2>
                    <p class="text-muted mb-4">
                        Your payment was successfully processed. Thank you for your purchase!
                    </p>
                    <div class="alert alert-success small shadow-sm">
                        <strong>Payment ID:</strong> {{ paymentIntentId }}
                    </div>
                </div>

                <!-- ✅ Order Confirmed -->
                <div v-else>
                    <div class="icon-wrapper bg-primary bg-opacity-10 text-primary mb-4 mx-auto">
                        <i class="bi bi-bag-check-fill display-5"></i>
                    </div>
                    <h2 class="fw-bold text-primary mb-2">Order Confirmed</h2>
                    <p class="text-muted mb-4">
                        Your order has been confirmed successfully. We’ll notify you once it’s ready.
                    </p>
                </div>

                <button class="btn btn-dark w-100 py-2 rounded-pill fw-semibold" @click="closeWindow">
                    <i class="bi bi-x-lg me-2"></i> Close Window
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
    paymentIntentId: {
        type: String,
        default: "",
    },
});

const closeWindow = () => window.close();
</script>

<style scoped>
/* Background Gradient */
.payment-success-wrapper {
    background: linear-gradient(135deg, #f9fafc 0%, #eef2f7 100%);
    padding: 1rem;
}

/* Card Styling */
.payment-card {
    max-width: 460px;
    width: 100%;
    border-radius: 1.25rem;
    background: #fff;
}

/* Icon Wrapper */
.icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.8s ease forwards;
}
</style>
