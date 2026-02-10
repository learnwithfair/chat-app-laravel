<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
});

const deleteForm = useForm({});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const deleteAccount = () => {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        deleteForm.delete(route('profile.destroy'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <Head title="Profile settings" />

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
                    Profile
                  </span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Title -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
              <p class="mt-1 text-sm text-gray-600">
                Manage your account information and preferences
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
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                ></path>
              </svg>
              Profile
            </a>
            <a
              href="/settings/password"
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
          <!-- Profile Information Card -->
          <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
          >
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
              <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
              <p class="mt-1 text-sm text-gray-600">Update your name and email address</p>
            </div>

            <div class="p-6">
              <form @submit.prevent="submit" class="space-y-6">
                <!-- Name Field -->
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Name
                  </label>
                  <input
                    id="name"
                    type="text"
                    required
                    autocomplete="name"
                    v-model="form.name"
                    placeholder="Full name"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed"
                    :class="{
                      'border-red-500 focus:border-red-500 focus:ring-red-500/10':
                        form.errors.name,
                    }"
                  />
                  <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                    {{ form.errors.name }}
                  </p>
                </div>

                <!-- Email Field -->
                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email address
                  </label>
                  <input
                    id="email"
                    type="email"
                    required
                    autocomplete="username"
                    v-model="form.email"
                    placeholder="email@example.com"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed"
                    :class="{
                      'border-red-500 focus:border-red-500 focus:ring-red-500/10':
                        form.errors.email,
                    }"
                  />
                  <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                    {{ form.errors.email }}
                  </p>
                </div>

                <!-- Email Verification Notice -->
                <div
                  v-if="mustVerifyEmail && !user.email_verified_at"
                  class="rounded-lg bg-yellow-50 border border-yellow-200 p-4"
                >
                  <div class="flex items-start">
                    <svg
                      class="h-5 w-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                      />
                    </svg>
                    <div class="flex-1">
                      <p class="text-sm text-yellow-800">
                        Your email address is unverified.
                        <Link
                          :href="route('verification.send')"
                          method="post"
                          as="button"
                          class="font-semibold text-yellow-900 hover:text-yellow-700 underline underline-offset-2 transition-colors duration-200 focus:outline-none"
                        >
                          Click here to resend the verification email.
                        </Link>
                      </p>

                      <div
                        v-if="status === 'verification-link-sent'"
                        class="mt-3 rounded-md bg-green-50 border border-green-200 p-3"
                      >
                        <div class="flex items-center">
                          <svg
                            class="h-4 w-4 text-green-600 mr-2"
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
                          <span class="text-sm font-medium text-green-800">
                            A new verification link has been sent to your email address.
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
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
                    <span>{{ form.processing ? "Saving..." : "Save changes" }}</span>
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
                      <span class="text-sm font-medium">Saved successfully!</span>
                    </div>
                  </Transition>
                </div>
              </form>
            </div>
          </div>

          <!-- Delete Account Card -->
          <div
            class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden"
          >
            <div class="border-b border-red-200 bg-red-50 px-6 py-4">
              <h2 class="text-lg font-semibold text-red-900">Delete Account</h2>
              <p class="mt-1 text-sm text-red-700">
                Permanently delete your account and all associated data
              </p>
            </div>

            <div class="p-6">
              <div class="rounded-lg bg-red-50 border border-red-200 p-4 mb-6">
                <div class="flex items-start">
                  <svg
                    class="h-5 w-5 text-red-600 mt-0.5 mr-3 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                  </svg>
                  <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-900 mb-1">
                      Warning: This action is irreversible
                    </h3>
                    <p class="text-sm text-red-800">
                      Once you delete your account, all of your data will be permanently
                      removed. This includes your profile, posts, comments, and any other
                      information associated with your account. This action cannot be
                      undone.
                    </p>
                  </div>
                </div>
              </div>

              <button
                type="button"
                @click="deleteAccount"
                :disabled="deleteForm.processing"
                class="px-6 py-3 rounded-lg bg-red-600 text-white font-semibold shadow-lg shadow-red-600/30 transition-all duration-200 hover:bg-red-700 hover:shadow-xl hover:shadow-red-600/40 focus:outline-none focus:ring-4 focus:ring-red-500/50 disabled:bg-gray-400 disabled:shadow-none disabled:cursor-not-allowed flex items-center gap-2"
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
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
                <span>{{
                  deleteForm.processing ? "Deleting..." : "Delete my account"
                }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
