<script setup>
import { ref } from "vue";
import Sidebar from "@/Components/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import ConfirmationModal from "@/Components/UI/ConfirmationModal.vue";
import { useConfirmation } from "@/Stores/useConfirmation";

const { state: confirmState } = useConfirmation();
const drawerOpen = ref(true);
</script>

<template>
    <div class="min-h-screen bg-base-100 font-sans">
        <div class="drawer lg:drawer-open">
            <input id="app-drawer" type="checkbox" class="drawer-toggle" />

            <div class="drawer-content flex flex-col bg-base-100">
                <!-- Top Navigation -->
                <Navbar />

                <!-- Main Content -->
                <main class="flex-grow p-4 lg:p-8">
                    <div class="mx-auto max-w-7xl">
                        <!-- Content Card -->
                        <div
                            class="rounded-2xl border border-white/10 dark:border-white/5 bg-base-200/40 p-6 backdrop-blur-md shadow-xl"
                            id="main-content-card"
                        >
                            <slot />
                        </div>
                    </div>
                </main>

                <!-- Footer -->
                <footer
                    class="footer footer-center p-4 text-base-content/50 border-t border-base-300"
                    id="app-footer"
                >
                    <aside>
                        <p>
                            Copyright © {{ new Date().getFullYear() }} -
                            <span class="font-bold text-primary"
                                >Server Management Panel</span
                            >
                        </p>
                    </aside>
                </footer>
            </div>

            <!-- Sidebar -->
            <div class="drawer-side z-20">
                <label
                    for="app-drawer"
                    aria-label="close sidebar"
                    class="drawer-overlay"
                    id="drawer-overlay"
                ></label>
                <Sidebar />
            </div>
        </div>

        <ConfirmationModal
            :show="confirmState.show"
            :title="confirmState.title"
            :message="confirmState.message"
            :confirm-label="confirmState.confirmLabel"
            :cancel-label="confirmState.cancelLabel"
            :type="confirmState.type"
            @confirm="confirmState.onConfirm"
            @cancel="confirmState.onCancel"
            @close="confirmState.show = false"
        />
    </div>
</template>
