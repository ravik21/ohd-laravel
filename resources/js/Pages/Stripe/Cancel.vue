<template>
    <div class="payment-canceled-wrapper d-flex justify-content-center align-items-center min-vh-100">
        <div class="card payment-card text-center shadow-lg border-0 p-4 p-md-5">
            <div class="card-body animate-fade-in">
                <div class="icon-wrapper bg-danger bg-opacity-10 text-danger mb-4 mx-auto">
                    <i class="bi bi-x-circle-fill display-5"></i>
                </div>

                <h2 class="fw-bold text-danger mb-2">Payment Canceled</h2>
                <p class="text-muted mb-4">
                    Your payment was canceled before completion. You can try again or contact support if the issue
                    persists.
                </p>

                <button class="btn btn-dark w-100 py-2 rounded-pill fw-semibold" @click="closeWindow">
                    <i class="bi bi-x-lg me-2"></i> Close Window
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const closeWindow = () => {
    window.close();
};
</script>

<style scoped>
/* Background Gradient */
.payment-canceled-wrapper {
    background: linear-gradient(135deg, #fdf3f3 0%, #f8f9fa 100%);
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
