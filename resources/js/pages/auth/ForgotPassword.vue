<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";

defineProps<{
  status?: string;
}>();

const form = useForm({
  email: "",
});

const submit = () => {
  form.post(route("password.email"));
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-12 sm:px-6 lg:px-8"
  >
    <Head title="Forgot password" />

    <div class="w-full max-w-md space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
          Forgot password
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Enter your email to receive a password reset link
        </p>
      </div>

      <!-- Card Container -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-10">
        <!-- Status Message -->
        <div
          v-if="status"
          class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-center text-sm font-medium text-green-800"
        >
          {{ status }}
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email address
            </label>
            <input
              id="email"
              type="email"
              name="email"
              required
              autofocus
              autocomplete="off"
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

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full px-4 py-3 rounded-lg bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30 transition-all duration-200 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/40 focus:outline-none focus:ring-4 focus:ring-blue-500/50 disabled:bg-gray-400 disabled:shadow-none disabled:cursor-not-allowed flex items-center justify-center gap-2"
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
            <span>{{
              form.processing ? "Sending link..." : "Email password reset link"
            }}</span>
          </button>
        </form>

        <!-- Back to Login Link -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Or, return to
            <a
              :href="route('login')"
              class="font-semibold text-blue-600 hover:text-blue-700 transition-colors duration-200 focus:outline-none focus:underline"
            >
              log in
            </a>
          </p>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-gray-500">
        We'll send you an email with instructions to reset your password
      </p>
    </div>
  </div>
</template>
