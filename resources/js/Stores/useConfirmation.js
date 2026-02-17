import { reactive } from "vue";

const state = reactive({
    show: false,
    title: "Confirm Action",
    message: "Are you sure you want to continue?",
    confirmLabel: "Confirm",
    cancelLabel: "Cancel",
    type: "primary",
    onConfirm: () => {},
});

export const useConfirmation = () => {
    const ask = (options = {}) => {
        return new Promise((resolve) => {
            state.title = options.title || "Confirm Action";
            state.message =
                options.message || "Are you sure you want to continue?";
            state.confirmLabel = options.confirmLabel || "Confirm";
            state.cancelLabel = options.cancelLabel || "Cancel";
            state.type = options.type || "primary";

            state.onConfirm = () => {
                state.show = false;
                resolve(true);
            };

            state.onCancel = () => {
                state.show = false;
                resolve(false);
            };

            state.show = true;
        });
    };

    return { state, ask };
};
