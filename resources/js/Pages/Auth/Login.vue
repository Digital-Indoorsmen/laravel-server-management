<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { LockClosedIcon, ShieldCheckIcon } from "@heroicons/vue/24/outline";

const form = useForm({
    email: "",
    password: "",
    remember: true,
});

const submit = () => {
    form.post(route("login.store"), {
        preserveScroll: true,
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Sign In" />

    <div
        class="min-h-screen bg-gradient-to-br from-base-100 via-base-200 to-base-100 flex items-center justify-center px-4 py-10"
    >
        <div class="w-full max-w-5xl grid gap-6 lg:grid-cols-2">
            <section
                class="hidden lg:flex flex-col justify-between rounded-3xl border border-base-300 bg-base-100 p-8 shadow-2xl"
            >
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-2 text-primary">
                        <ShieldCheckIcon class="h-5 w-5" />
                        <span class="text-xs font-semibold uppercase tracking-[0.2em]">Laravel Server Manager</span>
                    </div>

                    <h1 class="text-4xl font-black leading-tight">
                        Secure access to your infrastructure control panel.
                    </h1>

                    <p class="text-base-content/70">
                        Sign in with the panel admin account created by the installer to manage servers, keys, sites,
                        and deployment diagnostics.
                    </p>
                </div>

                <div class="rounded-2xl border border-base-300 bg-base-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-base-content/60">Security notes</p>
                    <ul class="mt-3 space-y-2 text-sm text-base-content/80">
                        <li>Session-based auth with CSRF protection.</li>
                        <li>Dashboard routes require authenticated access.</li>
                        <li>Use the installer-generated admin credentials.</li>
                    </ul>
                </div>
            </section>

            <section class="card border border-base-300 bg-base-100 shadow-2xl">
                <div class="card-body p-8 sm:p-10">
                    <div class="mb-6 space-y-2">
                        <h2 class="text-2xl font-bold">Panel Login</h2>
                        <p class="text-sm text-base-content/60">
                            Authenticate to continue to the server management dashboard.
                        </p>
                    </div>

                    <form class="space-y-4" @submit.prevent="submit">
                        <label class="form-control">
                            <div class="label">
                                <span class="label-text font-medium">Email</span>
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                class="input input-bordered w-full"
                                :class="{ 'input-error': form.errors.email }"
                                placeholder="admin@example.com"
                            />
                            <div v-if="form.errors.email" class="label">
                                <span class="label-text-alt text-error">{{ form.errors.email }}</span>
                            </div>
                        </label>

                        <label class="form-control">
                            <div class="label">
                                <span class="label-text font-medium">Password</span>
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                class="input input-bordered w-full"
                                :class="{ 'input-error': form.errors.password }"
                                placeholder="Enter password"
                            />
                            <div v-if="form.errors.password" class="label">
                                <span class="label-text-alt text-error">{{ form.errors.password }}</span>
                            </div>
                        </label>

                        <label class="label cursor-pointer justify-start gap-3">
                            <input v-model="form.remember" type="checkbox" class="checkbox checkbox-sm checkbox-primary" />
                            <span class="label-text">Remember this session</span>
                        </label>

                        <button
                            type="submit"
                            class="btn btn-primary w-full"
                            :disabled="form.processing"
                        >
                            <LockClosedIcon class="h-4 w-4" />
                            <span>{{ form.processing ? "Signing in..." : "Sign In" }}</span>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</template>
