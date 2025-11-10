<script setup>
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { loadStripeTerminal } from '@stripe/terminal-js'
import Swal from 'sweetalert2'
import axios from 'axios'
import topImage from '~/images/top.jpg'

const props = defineProps({
    ticketId: [String, Number],
    amount: [String, Number],
    customerId: String,
    repairItems: [Array, Object]
})

let steps = ref([
    'Creating connection token',
    'Discovering reader',
    'Connecting to reader',
    'Creating payment intent',
    'Collecting payment method',
    'Processing payment',
    'Payment successful'
])

const currentStep = ref(-1)
const stepStatus = ref(Array(steps.value.length).fill(''))
let terminal = null
let cancelCollectPayment = null
let currentPaymentIntentId = null
let isCollecting = false
let cancellingPaymentLoader = false

function setStep(index, status) {
    stepStatus.value = stepStatus.value.map((_, i) =>
        i < index ? 'done' : (i === index ? status : '')
    )
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms))
}

async function fetchService(service, payload = {}) {
    try {
        const { data } = await axios.post(`/api/stripe/${service}`, payload, {
            headers: { 'Content-Type': 'application/json' },
        })
        if (data.error) throw new Error(data.error)
        return data
    } catch (error) {
        if (error.response?.data?.error) {
            throw new Error(error.response.data.error)
        } else {
            throw new Error(error.message || 'Something went wrong')
        }
    }
}

async function showError(message) {
    await Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#ef4444',
    })
}

async function showSuccess(message) {
    await Swal.fire({
        icon: 'success',
        title: 'Success',
        text: message,
        confirmButtonColor: '#10b981',
    })
}

async function showInfo(message) {
    await Swal.fire({
        icon: 'info',
        title: 'Info',
        text: message,
        confirmButtonColor: '#3b82f6',
    })
}

async function runFlow() {
    try {
        setStep(0, 'active')
        await sleep(400)
        await fetchService('connection-token')
        setStep(0, 'done')

        const StripeTerminal = await loadStripeTerminal()

        terminal = StripeTerminal.create({
            onFetchConnectionToken: async () => {
                const token = await fetchService('connection-token')
                return token.secret
            },
            onUnexpectedReaderDisconnect: async () => {
                await showError('Reader disconnected unexpectedly. Please reconnect.')
            }
        })

        setStep(1, 'active')
        let discovery = null;

        if (import.meta.env.VITE_STRIPE_TERMINAL_SIMULATED === 'true') {
            discovery = await terminal.discoverReaders({ simulated: true })
        } else {
            discovery = await terminal.discoverReaders()
        }

        if (discovery.error || !discovery.discoveredReaders.length) {
            setStep(1, 'error')
            throw new Error('No readers found')
        }

        setStep(1, 'done')

        setStep(2, 'active')
        const connectResult = await terminal.connectReader(discovery.discoveredReaders[0])

        if (connectResult.error) {
            setStep(2, 'error')
            throw new Error(connectResult.error.message)
        }

        setStep(2, 'done')

        setStep(3, 'active')
        const paymentIntent = await fetchService('create-terminal-payment-intent', {
            amount: props.amount,
            customer_id: props.customerId,
        })
        currentPaymentIntentId = paymentIntent.id
        setStep(3, 'done')

        document.getElementById('cancel-btn').classList.remove('d-none')

        setStep(4, 'active')
        isCollecting = true
        cancelCollectPayment = async () => {
            if (isCollecting) {
                cancellingPaymentLoader = true
                await terminal.cancelCollectPaymentMethod()
                await showInfo('Payment collection cancelled.')
                document.getElementById('cancel-btn').classList.add('d-none')
                isCollecting = false
                cancellingPaymentLoader = false
            } else {
                await showInfo('No active payment collection to cancel.')
            }
        }

        const collectResult = await terminal.collectPaymentMethod(paymentIntent.client_secret)
        isCollecting = false

        if (collectResult.error) {
            setStep(4, 'error')

            if (collectResult.error.code != 'canceled') {
                throw new Error(collectResult.error.message)
            }

            return false;
        }

        setStep(4, 'done')

        setStep(5, 'active')
        const processResult = await terminal.processPayment(collectResult.paymentIntent)

        if (processResult.error) {
            setStep(5, 'error')
            throw new Error(processResult.error.message)
        }

        setStep(5, 'done')

        await fetchService(`${processResult.paymentIntent.id}/capture-payment`, { amount_to_capture: props.amount * 100 })
        setStep(6, 'done')
        document.getElementById('cancel-btn').classList.add('d-none')

        await showSuccess('Payment successful!')
    } catch (err) {
        console.error(err)

        await showError(err.message)
    }
}

onMounted(runFlow)

async function cancelPayment() {
    if (!cancelCollectPayment) return

    await cancelCollectPayment()
}
</script>

<template>
    <Head title="Terminal Payment" />
    <div class="checkout-wrapper">
        <div class="checkout-card row shadow-lg rounded-4 overflow-hidden">
            <!-- Left: Order Summary -->
            <div class="col-lg-6 col-md-12 summary-section">
                <div class="summary-content px-4 py-5">
                    <img :src="topImage" alt="Logo" class="img-fluid mb-4" />
                    <h3 class="fw-semibold mb-4 text-center text-md-start text-dark">
                        🧾 Order Summary
                    </h3>

                    <table class="table table-borderless align-middle mb-4">
                        <thead class="text-muted border-bottom">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) of repairItems" :key="i">
                                <td>{{ item.item }}</td>
                                <td class="text-end">${{ item.maximum_charge }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="my-3" />

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold fs-5 text-dark">Total</span>
                        <span class="fw-bold fs-4 text-primary">${{ amount }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Payment Section -->
            <div class="col-lg-6 col-md-12 payment-section d-flex align-items-center justify-content-center">
                <div class="payment-form w-100 px-4 py-5">
                    <div class="text-center mb-4">
                        <h4 class="fw-semibold text-dark mb-2">💳 Ready for Payment</h4>
                        <p class="text-muted mb-0">Please swipe or tap your card when the device prompts you.</p>
                    </div>

                    <!-- Steps -->
                    <ul class="list-group list-group-flush text-start mb-3">
                        <li v-for="(step, i) in steps" :key="i"
                            class="list-group-item border-0 d-flex align-items-center py-2 px-0">
                            <div class="step-circle me-3 d-flex align-items-center justify-content-center" :class="{
                                'bg-gradient-primary text-white shadow': stepStatus[i] === 'active',
                                'bg-success text-white shadow-sm': stepStatus[i] === 'done',
                                'bg-secondary bg-opacity-25 text-muted': stepStatus[i] === 'pending',
                                'bg-danger text-white': stepStatus[i] === 'error'
                            }">
                                <div v-if="stepStatus[i] === 'active'"
                                    class="spinner-border spinner-border-sm text-white"></div>
                                <span v-else-if="stepStatus[i] === 'done'">✓</span>
                                <span v-else-if="stepStatus[i] === 'error'">✗</span>
                                <span v-else class="small">{{ i + 1 }}</span>
                            </div>

                            <span :class="{
                                'fw-semibold text-dark': stepStatus[i] === 'active',
                                'text-success': stepStatus[i] === 'done',
                                'text-danger': stepStatus[i] === 'error',
                                'text-muted': stepStatus[i] === 'pending'
                            }">
                                {{ step }}
                            </span>
                        </li>
                    </ul>

                    <!-- Cancel Button -->
                    <button id="cancel-btn" class="btn btn-danger w-100 px-4 mt-0 d-none"
                        @click="cancelPayment">
                        <template v-if="cancellingPaymentLoader">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div> Canceling Payment
                        </template>
                        <template v-else>
                            <i class="bi bi-x-circle me-2"></i> Cancel Payment
                        </template>
                    </button>

                    <!-- Note -->
                    <div class="alert alert-warning d-flex align-items-center mt-4 rounded-3">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <span>
                            Do not close this window while the payment is being processed.
                            If the payment page doesn't load, you may refresh to retry.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.checkout-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8faff 0%, #eef3ff 100%);
    padding: 2rem 1rem;
}

.checkout-card {
    background: #fff;
    border-radius: 1.5rem;
    max-width: 1100px;
    width: 100%;
    overflow: hidden;
}

.summary-section {
    background-color: #f9fbff;
    border-right: 1px solid #e5e9f0;
}

.payment-section {
    background-color: #ffffff;
}

.step-circle {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    font-weight: 600;
    transition: all 0.3s ease;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #6d5dfc, #4c4eff);
}

.table th {
    font-weight: 600;
}

.alert {
    font-size: 0.9rem;
    background-color: #fff7e6;
    border: 1px solid #ffe3b3;
}

@keyframes pulse {
    from {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(99, 91, 255, 0.4);
    }

    to {
        transform: scale(1.1);
        box-shadow: 0 0 10px 5px rgba(99, 91, 255, 0.15);
    }
}

.step-circle.bg-gradient-primary {
    animation: pulse 1.2s ease-in-out infinite alternate;
}

/* Responsive */
@media (max-width: 768px) {
    .summary-section {
        border-right: none;
        border-bottom: 1px solid #e5e9f0;
    }

    .checkout-card {
        border-radius: 0.75rem;
    }
}
</style>
