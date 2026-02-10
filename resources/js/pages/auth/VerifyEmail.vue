<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";

defineProps<{
  status?: string;
}>();

const form = useForm({});

const submit = () => {
  form.post(route("verification.send"));
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-12 sm:px-6 lg:px-8"
  >
    <Head title="Email verification" />

    <div class="w-full max-w-md space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div
          class="mx-auto h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center mb-4"
        >
          <svg
            class="h-8 w-8 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
          </svg>
        </div>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
          Verify email
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Please verify your email address by clicking on the link we just emailed to you.
        </p>
      </div>

      <!-- Card Container -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-10">
        <!-- Status Message -->
        <div
          v-if="status === 'verification-link-sent'"
          class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-center"
        >
          <div class="flex items-center justify-center mb-2">
            <svg
              class="h-5 w-5 text-green-600 mr-2"
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
            <span class="text-sm font-medium text-green-800">Email sent!</span>
          </div>
          <p class="text-sm text-green-700">
            A new verification link has been sent to the email address you provided during
            registration.
          </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-6 text-center">
          <!-- Resend Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full px-4 py-3 rounded-lg bg-gray-100 text-gray-700 font-semibold border border-gray-300 transition-all duration-200 hover:bg-gray-200 hover:border-gray-400 focus:outline-none focus:ring-4 focus:ring-gray-300/50 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
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
            <svg
              v-else
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            <span>{{
              form.processing ? "Sending..." : "Resend verification email"
            }}</span>
          </button>

          <!-- Logout Link -->
          <form method="post" :action="route('logout')" class="inline">
            <button
              type="submit"
              class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200 focus:outline-none focus:underline underline-offset-2"
            >
              Log out
            </button>
          </form>
        </form>
      </div>

      <!-- Footer -->
      <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
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
          <p class="text-xs text-blue-800">
            Check your spam folder if you don't see the email in your inbox. The link will
            expire in 60 minutes.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
