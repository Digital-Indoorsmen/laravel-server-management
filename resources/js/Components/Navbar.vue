<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import {
    MagnifyingGlassIcon,
    Bars3Icon,
    SwatchIcon,
    ChevronDownIcon,
} from "@heroicons/vue/24/outline";

const page = usePage();
const user = computed(
    () => page.props.auth?.user || { name: "Guest", email: "" },
);

const themes = [
    { name: "Light", value: "light" },
    { name: "Dark", value: "dark" },
    { name: "Corporate", value: "corporate" },
    { name: "Business", value: "business" },
];
</script>

<template>
    <div
        class="navbar bg-base-100 border-b border-base-300 px-4 h-16 sticky top-0 z-10"
        id="main-navbar"
    >
        <!-- Sidebar Toggle -->
        <div class="flex-none lg:hidden">
            <label
                for="app-drawer"
                class="btn btn-square btn-ghost"
                id="sidebar-toggle-mobile"
            >
                <Bars3Icon class="h-6 w-6" />
            </label>
        </div>

        <!-- Search Bar -->
        <div class="flex-1 px-2 mx-2">
            <div class="relative w-full max-w-md">
                <div
                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-base-content/50"
                >
                    <MagnifyingGlassIcon class="h-4 w-4" />
                </div>
                <input
                    type="text"
                    id="global-search"
                    placeholder="Search systems, services..."
                    class="input input-sm input-bordered w-full pl-10 focus:input-primary bg-base-200/50 border-none transition-all duration-300"
                />
            </div>
        </div>

        <!-- Right Side -->
        <div class="flex-none flex items-center gap-2">
            <!-- Theme Controller Dropdown -->
            <div class="dropdown dropdown-end">
                <div
                    tabindex="0"
                    role="button"
                    class="btn btn-ghost btn-sm gap-2"
                    id="theme-selector"
                >
                    <SwatchIcon class="h-5 w-5 opacity-60" />
                    <span class="hidden sm:inline">Theme</span>
                    <ChevronDownIcon class="h-3 w-3 opacity-40" />
                </div>
                <ul
                    tabindex="0"
                    class="dropdown-content menu menu-sm p-2 shadow-2xl bg-base-100 border border-base-300 rounded-box w-52 mt-3 z-[1]"
                    id="theme-list"
                >
                    <li v-for="theme in themes" :key="theme.value">
                        <input
                            type="radio"
                            name="theme-dropdown"
                            class="theme-controller btn btn-sm btn-ghost justify-start font-medium"
                            :aria-label="theme.name"
                            :value="theme.value"
                            :id="`theme-${theme.value}`"
                        />
                    </li>
                </ul>
            </div>

            <!-- User Menu -->
            <div class="dropdown dropdown-end">
                <div
                    tabindex="0"
                    role="button"
                    class="btn btn-ghost btn-circle avatar"
                    id="user-menu-toggle"
                >
                    <div
                        class="w-8 rounded-full bg-primary text-primary-content flex items-center justify-center font-bold"
                    >
                        {{ user.name.charAt(0) }}
                    </div>
                </div>
                <ul
                    tabindex="0"
                    class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-xl bg-base-100 border border-base-300 rounded-box w-52"
                    id="user-menu-list"
                >
                    <div class="px-4 py-2 border-b border-base-300 mb-2">
                        <p class="text-sm font-bold">{{ user.name }}</p>
                        <p class="text-xs text-base-content/60 truncate">
                            {{ user.email }}
                        </p>
                    </div>
                    <li><Link href="#" id="profile-link">Profile</Link></li>
                    <li>
                        <Link :href="route('settings.index')" id="settings-link"
                            >Settings</Link
                        >
                    </li>

                    <li>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="text-error w-full text-left"
                            id="logout-button"
                            >Logout</Link
                        >
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
