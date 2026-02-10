<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";

interface Props {
  token: string;
  email: string;
}

const props = defineProps<Props>();

const form = useForm({
  token: props.token,
  email: props.email,
  password: "",
  password_confirmation: "",
});

const submit = () => {
  form.post(route("password.store"), {
    onFinish: () => {
      form.reset("password", "password_confirmation");
    },
  });
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-12 sm:px-6 lg:px-8"
  >
    <Head title="Reset password" />

    <div class="w-full max-w-md space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
          Reset password
        </h2>
        <p class="mt-2 text-sm text-gray-600">Please enter your new password below</p>
      </div>

      <!-- Card Container -->
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-10">
        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Email Field (Read-only) -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email
            </label>
            <input
              id="email"
              type="email"
              name="email"
              readonly
              autocomplete="email"
              v-model="form.email"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed transition-all duration-200 focus:outline-none"
            />
            <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <input
              id="password"
              type="password"
              name="password"
              required
              autofocus
              autocomplete="new-password"
              v-model="form.password"
              placeholder="New password"
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

          <!-- Password Confirmation Field -->
          <div>
            <label
              for="password_confirmation"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              Confirm Password
            </label>
            <input
              id="password_confirmation"
              type="password"
              name="password_confirmation"
              required
              autocomplete="new-password"
              v-model="form.password_confirmation"
              placeholder="Confirm new password"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed"
              :class="{
                'border-red-500 focus:border-red-500 focus:ring-red-500/10':
                  form.errors.password_confirmation,
              }"
            />
            <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">
              {{ form.errors.password_confirmation }}
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
              form.processing ? "Resetting password..." : "Reset password"
            }}</span>
          </button>
        </form>
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-gray-500">
        After resetting, you'll be able to log in with your new password
      </p>
    </div>
  </div>
</template>
