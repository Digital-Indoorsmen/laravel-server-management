<script setup>
import { IconPackage } from "@tabler/icons-vue";

const props = defineProps({
    site: Object,
});
</script>

<template>
    <section class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body items-center text-center py-16 gap-4">
            <div class="bg-base-200 p-4 rounded-full">
                <IconPackage class="h-12 w-12 text-base-content/20" />
            </div>
            <div>
                <h3 class="text-xl font-medium">Composer Credentials</h3>
                <p class="text-sm text-base-content/60 max-w-sm mx-auto">
                    Manage <code>auth.json</code> credentials for private package repositories like Nova, Spark, or private Satis instances.
                </p>
            </div>
            <div class="card-actions">
                <button class="btn btn-primary btn-disabled">
                    + Add Credential
                </button>
            </div>
            <p class="text-xs text-base-content/40 uppercase tracking-widest font-bold mt-4">Coming Soon</p>
        </div>
    </section>
</template>
