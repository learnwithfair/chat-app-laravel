<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const updatePassword = () => {
  form.put(route("password.update"), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: (errors: any) => {
      if (errors.password) {
        form.reset("password", "password_confirmation");
        if (passwordInput.value instanceof HTMLInputElement) {
          passwordInput.value.focus();
        }
      }

      if (errors.current_password) {
        form.reset("current_password");
        if (currentPasswordInput.value instanceof HTMLInputElement) {
          currentPasswordInput.value.focus();
        }
      }
    },
  });
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <Head title="Security settings" />

    <!-- Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-6">
          <!-- Breadcrumb -->
          <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
              <li class="inline-flex items-center">
                <a
                  href="/"
                  class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors duration-200"
                >
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
                    ></path>
                  </svg>
                  Home
                </a>
              </li>
              <li>
                <div class="flex items-center">
                  <svg
                    class="w-5 h-5 text-gray-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                      clip-rule="evenodd"
                    ></path>
                  </svg>
                  <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                    Settings
                  </span>
                </div>
              </li>
              <li aria-current="page">
                <div class="flex items-center">
                  <svg
                    class="w-5 h-5 text-gray-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                      clip-rule="evenodd"
                    ></path>
                  </svg>
                  <span class="ml-1 text-sm font-medium text-gray-900 md:ml-2">
                    Security
                  </span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Title -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Security Settings</h1>
              <p class="mt-1 text-sm text-gray-600">
                Manage your password and account security
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
          <nav
            class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-1 sticky top-6"
          >
            <a
              href="/settings/profile"
              class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200"
            >
              <svg
                class="w-5 h-5 mr-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                ></path>
              </svg>
              Profile
            </a>
            <a
              href="/settings/password"
              class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-blue-50 text-blue-700 border border-blue-200"
            >
              <svg
                class="w-5 h-5 mr-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                ></path>
              </svg>
              Security
            </a>
            <a
              href="/settings/notifications"
              class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200"
            >
              <svg
                class="w-5 h-5 mr-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                ></path>
              </svg>
              Notifications
            </a>
          </nav>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Update Password Card -->
          <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
          >
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h2 class="text-lg font-semibold text-gray-900">Update Password</h2>
              <p class="mt-1 text-sm text-gray-600">
                Ensure your account is using a long, random password to stay secure
              </p>
            </div>

            <div class="p-6">
              <!-- Security Tips -->
              <div class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4">
                <div class="flex items-start">
                  <svg
                    class="h-5 w-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                  <div class="flex-1">
                    <h3 class="text-sm font-semibold text-blue-900 mb-1">
                      Password Security Tips
                    </h3>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                      <li>
                        Use at least 8 characters with a mix of letters, numbers, and
                        symbols
                      </li>
                      <li>Avoid common words and personal information</li>
                      <li>Don't reuse passwords from other accounts</li>
                    </ul>
                  </div>
                </div>
              </div>

              <form @submit.prevent="updatePassword" class="space-y-6">
                <!-- Current Password Field -->
                <div>
                  <label
                    for="current_password"
                    class="block text-sm font-medium text-gray-700 mb-2"
                  >
                    Current password
                  </label>
                  <input
                    id="current_password"
                    ref="currentPasswordInput"
                    type="password"
                    autocomplete="current-password"
                    v-model="form.current_password"
                    placeholder="Enter your current password"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed"
                    :class="{
                      'border-red-500 focus:border-red-500 focus:ring-red-500/10':
                        form.errors.current_password,
                    }"
                  />
                  <p
                    v-if="form.errors.current_password"
                    class="mt-2 text-sm text-red-600"
                  >
                    {{ form.errors.current_password }}
                  </p>
                </div>

                <!-- New Password Field -->
                <div>
                  <label
                    for="password"
                    class="block text-sm font-medium text-gray-700 mb-2"
                  >
                    New password
                  </label>
                  <input
                    id="password"
                    ref="passwordInput"
                    type="password"
                    autocomplete="new-password"
                    v-model="form.password"
                    placeholder="Enter your new password"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed"
                    :class="{
                      'border-red-500 focus:border-red-500 focus:ring-red-500/10':
                        form.errors.password,
                    }"
                  />
                  <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                    {{ form.errors.password }}
                  </p>
                </div>

                <!-- Confirm Password Field -->
                <div>
                  <label
                    for="password_confirmation"
                    class="block text-sm font-medium text-gray-700 mb-2"
                  >
                    Confirm password
                  </label>
                  <input
                    id="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    v-model="form.password_confirmation"
                    placeholder="Confirm your new password"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed"
                    :class="{
                      'border-red-500 focus:border-red-500 focus:ring-red-500/10':
                        form.errors.password_confirmation,
                    }"
                  />
                  <p
                    v-if="form.errors.password_confirmation"
                    class="mt-2 text-sm text-red-600"
                  >
                    {{ form.errors.password_confirmation }}
                  </p>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center gap-4">
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-3 rounded-lg bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/40 focus:outline-none focus:ring-4 focus:ring-blue-500/50 disabled:bg-gray-400 disabled:shadow-none disabled:cursor-not-allowed flex items-center gap-2"
                  >
                    <svg
                      v-if="form.processing"
                      class="h-5 w-5 animate-spin"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                      ></circle>
                      <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                      ></path>
                    </svg>
                    <span>{{ form.processing ? "Updating..." : "Update password" }}</span>
                  </button>

                  <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 transform scale-95"
                    enter-to-class="opacity-100 transform scale-100"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-from-class="opacity-100 transform scale-100"
                    leave-to-class="opacity-0 transform scale-95"
                  >
                    <div
                      v-show="form.recentlySuccessful"
                      class="flex items-center gap-2 text-green-600"
                    >
                      <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M5 13l4 4L19 7"
                        />
                      </svg>
                      <span class="text-sm font-medium"
                        >Password updated successfully!</span
                      >
                    </div>
                  </Transition>
                </div>
              </form>
            </div>
          </div>

          <!-- Additional Security Card -->
          <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
          >
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h2 class="text-lg font-semibold text-gray-900">Additional Security</h2>
              <p class="mt-1 text-sm text-gray-600">
                Other ways to keep your account secure
              </p>
            </div>

            <div class="p-6 space-y-4">
              <!-- Two-Factor Authentication -->
              <div
                class="flex items-start justify-between p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors duration-200"
              >
                <div class="flex items-start">
                  <div
                    class="flex-shrink-0 h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center"
                  >
                    <svg
                      class="h-6 w-6 text-green-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-semibold text-gray-900">
                      Two-Factor Authentication
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                      Add an extra layer of security to your account
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-gray-200"
                >
                  Enable
                </button>
              </div>

              <!-- Login History -->
              <div
                class="flex items-start justify-between p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors duration-200"
              >
                <div class="flex items-start">
                  <div
                    class="flex-shrink-0 h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center"
                  >
                    <svg
                      class="h-6 w-6 text-blue-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-semibold text-gray-900">Login History</h3>
                    <p class="mt-1 text-sm text-gray-600">
                      View your recent login activity and sessions
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-gray-200"
                >
                  View
                </button>
              </div>

              <!-- Active Sessions -->
              <div
                class="flex items-start justify-between p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors duration-200"
              >
                <div class="flex items-start">
                  <div
                    class="flex-shrink-0 h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center"
                  >
                    <svg
                      class="h-6 w-6 text-purple-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                      />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-semibold text-gray-900">Active Sessions</h3>
                    <p class="mt-1 text-sm text-gray-600">
                      Manage devices where you're currently logged in
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-gray-200"
                >
                  Manage
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
