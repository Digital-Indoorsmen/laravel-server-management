<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    KeyIcon,
    TrashIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    sshKeys: Array,
});

const activeTab = ref("generate");

const keyForm = useForm({
    name: "",
    type: "ed25519",
});

const importForm = useForm({
    name: "",
    public_key: "",
});

const generateKey = () => {
    keyForm.post(route("ssh-keys.store"), {
        preserveScroll: true,
        onSuccess: () => keyForm.reset(),
    });
};

const importKey = () => {
    importForm.post(route("ssh-keys.import"), {
        preserveScroll: true,
        onSuccess: () => importForm.reset(),
    });
};

const deleteKey = (id) => {
    if (confirm("Are you sure you want to delete this SSH key?")) {
        useForm({}).delete(route("ssh-keys.destroy", { sshKey: id }), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header -->
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2"
                >
                    <KeyIcon class="h-8 w-8 text-primary" />
                    SSH Keys
                </h1>
                <p class="text-base-content/60">
                    Manage your SSH keys for secure server access and automated
                    deployments.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pb-12">
                <!-- Sidebar Info -->
                <div class="space-y-4">
                    <h2 class="text-lg font-bold">Your Keys</h2>
                    <p class="text-sm text-base-content/60">
                        These keys are used by the panel to communicate with
                        your servers. Private keys are encrypted at rest.
                    </p>
                </div>

                <!-- SSH Keys Content -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Key List -->
                    <div v-if="sshKeys.length > 0" class="space-y-3">
                        <div
                            v-for="key in sshKeys"
                            :key="key.id"
                            class="card bg-base-100 border border-base-300 shadow-sm p-4 flex flex-row items-center justify-between gap-4"
                        >
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 p-2 rounded-lg">
                                    <KeyIcon class="h-5 w-5 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold truncate">
                                        {{ key.name }}
                                    </div>
                                    <div
                                        class="text-[10px] font-mono opacity-50 truncate text-primary"
                                    >
                                        {{ key.fingerprint }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a
                                    v-if="key.private_key"
                                    :href="
                                        route('ssh-keys.download', {
                                            sshKey: key.id,
                                        })
                                    "
                                    class="btn btn-ghost btn-sm text-primary hover:bg-primary/10"
                                    title="Download Private Key"
                                >
                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                </a>
                                <button
                                    @click="deleteKey(key.id)"
                                    class="btn btn-ghost btn-sm text-error hover:bg-error/10"
                                    title="Delete Key"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="card bg-base-200/50 border border-dashed border-base-300 p-8 text-center"
                    >
                        <KeyIcon
                            class="h-10 w-10 text-base-content/20 mx-auto"
                        />
                        <p class="text-sm text-base-content/60 mt-2">
                            No SSH keys found. Add one to get started.
                        </p>
                    </div>

                    <!-- Add/Import Tabs -->
                    <div
                        class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden"
                    >
                        <div
                            class="tabs tabs-box w-full bg-base-200/50 border-b border-base-300 justify-start"
                        >
                            <button
                                @click="activeTab = 'generate'"
                                class="tab gap-2"
                                :class="{
                                    'tab-active': activeTab === 'generate',
                                }"
                            >
                                <PlusIcon class="h-4 w-4" />
                                Generate New
                            </button>
                            <button
                                @click="activeTab = 'import'"
                                class="tab gap-2"
                                :class="{
                                    'tab-active': activeTab === 'import',
                                }"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                Import Existing
                            </button>
                        </div>

                        <div class="card-body p-6">
                            <!-- Generate Form -->
                            <form
                                v-if="activeTab === 'generate'"
                                @submit.prevent="generateKey"
                                class="space-y-4"
                            >
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                >
                                    <div class="form-control">
                                        <label class="label"
                                            ><span class="label-text-alt"
                                                >Key Name</span
                                            ></label
                                        >
                                        <input
                                            v-model="keyForm.name"
                                            placeholder="e.g. Panel Key"
                                            class="input input-bordered"
                                            required
                                        />
                                    </div>
                                    <div class="form-control">
                                        <label class="label"
                                            ><span class="label-text-alt"
                                                >Key Type</span
                                            ></label
                                        >
                                        <select
                                            v-model="keyForm.type"
                                            class="select select-bordered"
                                        >
                                            <option value="ed25519">
                                                Ed25519 (Recommended)
                                            </option>
                                            <option value="rsa">
                                                RSA (4096-bit)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-actions justify-end">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="keyForm.processing"
                                    >
                                        <span
                                            v-if="keyForm.processing"
                                            class="loading loading-spinner"
                                        ></span>
                                        Generate Pair
                                    </button>
                                </div>
                            </form>

                            <!-- Import Form -->
                            <form
                                v-else
                                @submit.prevent="importKey"
                                class="space-y-4"
                            >
                                <div class="space-y-4">
                                    <div class="form-control">
                                        <label class="label"
                                            ><span class="label-text-alt"
                                                >Key Name</span
                                            ></label
                                        >
                                        <input
                                            v-model="importForm.name"
                                            placeholder="e.g. My Mac Pro"
                                            class="input input-bordered"
                                            required
                                        />
                                    </div>

                                    <div class="form-control">
                                        <label
                                            class="label flex justify-between"
                                        >
                                            <span class="label-text-alt"
                                                >Public Key</span
                                            >
                                            <span
                                                class="label-text-alt opacity-50 font-mono"
                                                >Starts with ssh-rsa or
                                                ssh-ed25519</span
                                            >
                                        </label>
                                        <textarea
                                            v-model="importForm.public_key"
                                            placeholder="Paste your public key here..."
                                            class="textarea textarea-bordered h-32 font-mono text-xs"
                                            required
                                        ></textarea>
                                    </div>

                                    <div
                                        class="bg-base-200 rounded-lg p-4 space-y-3"
                                    >
                                        <h4
                                            class="text-xs font-bold uppercase tracking-wider opacity-60"
                                        >
                                            Instructions
                                        </h4>
                                        <p class="text-xs">
                                            To copy your public key from a
                                            terminal, run:
                                        </p>
                                        <div class="join w-full">
                                            <code
                                                class="join-item bg-black text-xs p-3 text-white flex-1 font-mono"
                                                >pbcopy &lt;
                                                ~/.ssh/id_rsa.pub</code
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    navigator.clipboard.writeText(
                                                        'pbcopy < ~/.ssh/id_rsa.pub',
                                                    )
                                                "
                                                class="join-item btn btn-square btn-sm h-auto"
                                                title="Copy command"
                                            >
                                                <DocumentDuplicateIcon
                                                    class="h-4 w-4"
                                                />
                                            </button>
                                        </div>
                                        <p
                                            class="text-[10px] opacity-60 italic"
                                        >
                                            * Replace id_rsa.pub with
                                            id_ed25519.pub if you use Ed25519.
                                        </p>
                                    </div>
                                </div>
                                <div class="card-actions justify-end">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="importForm.processing"
                                    >
                                        <span
                                            v-if="importForm.processing"
                                            class="loading loading-spinner"
                                        ></span>
                                        Import Key
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
