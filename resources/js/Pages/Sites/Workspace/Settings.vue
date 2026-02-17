<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import SiteWorkspaceLayout from "@/Components/Sites/SiteWorkspaceLayout.vue";
import GeneralSettings from "@/Components/Sites/Settings/GeneralSettings.vue";
import DeploymentSettings from "@/Components/Sites/Settings/DeploymentSettings.vue";
import EnvironmentSettings from "@/Components/Sites/Settings/EnvironmentSettings.vue";
import ComposerSettings from "@/Components/Sites/Settings/ComposerSettings.vue";
import NotificationSettings from "@/Components/Sites/Settings/NotificationSettings.vue";
import IntegrationSettings from "@/Components/Sites/Settings/IntegrationSettings.vue";
import {
    IconSettings,
    IconRocket,
    IconVariable,
    IconPackage,
    IconBell,
    IconPlugConnected,
} from "@tabler/icons-vue";

const props = defineProps({
    site: Object,
    workspace: Object,
    activeSection: String,
    phpVersions: Array,
    appTypes: Array,
    envContent: String, // Lazy prop
});

const sections = [
    { key: "general", label: "General", icon: IconSettings },
    { key: "deployments", label: "Deployments", icon: IconRocket },
    { key: "environment", label: "Environment", icon: IconVariable },
    { key: "composer", label: "Composer", icon: IconPackage },
    { key: "notifications", label: "Notifications", icon: IconBell },
    { key: "integrations", label: "Integrations", icon: IconPlugConnected },
];

const activeComponent = computed(() => {
    switch (props.activeSection) {
        case "general":
            return GeneralSettings;
        case "deployments":
            return DeploymentSettings;
        case "environment":
            return EnvironmentSettings;
        case "composer":
            return ComposerSettings;
        case "notifications":
            return NotificationSettings;
        case "integrations":
            return IntegrationSettings;
        default:
            return GeneralSettings;
    }
});
</script>

<template>
    <SiteWorkspaceLayout :site="site" :workspace="workspace">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <aside class="w-full md:w-64 shrink-0">
                <nav class="space-y-1">
                    <Link
                        v-for="section in sections"
                        :key="section.key"
                        :href="route('sites.workspace.settings', [site.id, section.key])"
                        class="flex items-center gap-3 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="
                            activeSection === section.key
                                ? 'bg-primary text-primary-content'
                                : 'text-base-content/70 hover:bg-base-200 hover:text-base-content'
                        "
                    >
                        <component :is="section.icon" class="h-4 w-4" />
                        {{ section.label }}
                    </Link>
                </nav>
            </aside>

            <!-- Content Panel -->
            <main class="flex-1 min-w-0">
                <component
                    :is="activeComponent"
                    :site="site"
                    :php-versions="phpVersions"
                    :app-types="appTypes"
                    :env-content="envContent"
                />
            </main>
        </div>
    </SiteWorkspaceLayout>
</template>
