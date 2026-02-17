<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import {
    Squares2X2Icon,
    ServerIcon,
    GlobeAltIcon,
    CircleStackIcon,
    KeyIcon,
    CommandLineIcon,
} from "@heroicons/vue/24/outline";

const page = usePage();

const currentUrl = computed(() => page.url);

const navigation = computed(() => [
    {
        name: "Dashboard",
        href: route("dashboard"),
        icon: Squares2X2Icon,
        startsWith: "/dashboard",
    },
    {
        name: "System",
        href: route("system.index"),
        icon: ServerIcon,
        startsWith: "/system",
    },
    {
        name: "Sites",
        href: route("sites.catalog"),
        icon: GlobeAltIcon,
        startsWith: "/sites",
    },
    {
        name: "Databases",
        href: route("databases.index"),
        icon: CircleStackIcon,
        startsWith: "/databases",
    },
    {
        name: "DB Engines",
        href: page.props.hostServerId
            ? route("servers.database-engines.index", page.props.hostServerId)
            : "#",
        icon: CommandLineIcon,
        startsWith: "/servers",
        hidden: !page.props.hostServerId,
    },
    {
        name: "SSH Keys",
        href: route("ssh-keys.index"),
        icon: KeyIcon,
        startsWith: "/ssh-keys",
    },
]);

const isActive = (startsWith) => {
    return (
        currentUrl.value === startsWith ||
        currentUrl.value.startsWith(`${startsWith}/`)
    );
};
</script>

<template>
    <div
        class="flex min-h-full flex-col bg-base-200 text-base-content w-64 transition-all duration-300 border-r border-base-300"
    >
        <!-- Logo Section -->
        <div class="flex h-16 items-center px-4 border-b border-base-300">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-content"
                >
                    <ServerIcon class="h-5 w-5" />
                </div>
                <span class="text-xl font-bold tracking-tight"> Panel </span>
            </div>
        </div>

        <!-- Navigation -->
        <ul class="menu w-full grow p-2 gap-1 px-3">
            <template v-for="item in navigation" :key="item.name">
                <li v-if="!item.hidden">
                    <Link
                        :href="item.href"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-base-300 transition-colors"
                        :class="{
                            'bg-primary text-primary-content hover:bg-primary/90':
                                isActive(item.startsWith),
                        }"
                        :title="item.name"
                    >
                        <component :is="item.icon" class="h-5 w-5 shrink-0" />
                        <span class="truncate font-medium">
                            {{ item.name }}
                        </span>
                    </Link>
                </li>
            </template>
        </ul>

        <!-- Bottom Section -->
        <div class="p-3 border-t border-base-300">
            <Link
                :href="route('settings.index')"
                class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-base-300 transition-colors"
                :class="{
                    'bg-primary text-primary-content hover:bg-primary/90':
                        $page.component === 'Settings',
                }"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="size-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4.5 12a7.5 7.5 0 0 0 15 0m-15 0a7.5 7.5 0 1 1 15 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077 1.41-.513m14.095-5.128 1.41-.513M5.106 17.785l1.15-.964m11.49-9.642 1.149-.964M7.501 19.795l.75-1.3m7.5-12.99.75-1.3m3.065 3.077-1.41-.513m-14.095 5.128-1.41-.513M18.894 17.785l-1.15-.964M6.255 7.179l-1.15-.964M16.5 19.795l-.75-1.3m-7.5-12.99-.75-1.3"
                    />
                </svg>
                <span class="text-sm">Settings</span>
            </Link>
        </div>
    </div>
</template>
