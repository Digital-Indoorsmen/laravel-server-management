<script setup>
import { ref, watch } from "vue";
import { ExclamationTriangleIcon, XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "Confirm Action",
    },
    message: {
        type: String,
        default: "Are you sure you want to perform this action?",
    },
    confirmLabel: {
        type: String,
        default: "Confirm",
    },
    cancelLabel: {
        type: String,
        default: "Cancel",
    },
    type: {
        type: String,
        default: "primary", // primary, error, warning
    },
});

const emit = defineEmits(["confirm", "cancel", "close"]);

const dialog = ref(null);

watch(
    () => props.show,
    (show) => {
        if (show) {
            dialog.value?.showModal();
        } else {
            dialog.value?.close();
        }
    },
);

const handleCancel = () => {
    emit("cancel");
    emit("close");
};

const handleConfirm = () => {
    emit("confirm");
    emit("close");
};
</script>

<template>
    <dialog
        ref="dialog"
        class="modal modal-bottom sm:modal-middle backdrop-blur-md"
        @close="$emit('close')"
    >
        <div
            class="modal-box border border-white/10 shadow-2xl bg-base-100/90 p-0 overflow-hidden"
        >
            <div class="p-6">
                <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="handleCancel"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>

                <div class="flex items-start gap-4">
                    <div
                        class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                        :class="{
                            'bg-primary/10 text-primary': type === 'primary',
                            'bg-error/10 text-error': type === 'error',
                            'bg-warning/10 text-warning': type === 'warning',
                        }"
                    >
                        <ExclamationTriangleIcon class="h-6 w-6" />
                    </div>

                    <div class="mt-1 flex-1">
                        <h3 class="text-lg font-bold text-base-content">
                            {{ title }}
                        </h3>
                        <p
                            class="py-2 text-sm text-base-content/70 leading-relaxed"
                        >
                            {{ message }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="bg-base-200/50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-2"
            >
                <button
                    class="btn"
                    :class="{
                        'btn-primary': type === 'primary',
                        'btn-error': type === 'error',
                        'btn-warning': type === 'warning',
                    }"
                    @click="handleConfirm"
                >
                    {{ confirmLabel }}
                </button>
                <button class="btn btn-ghost" @click="handleCancel">
                    {{ cancelLabel }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="handleCancel">close</button>
        </form>
    </dialog>
</template>
