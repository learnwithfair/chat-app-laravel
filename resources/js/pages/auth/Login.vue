<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";

defineProps<{
  status?: string;
  canResetPassword: boolean;
}>();

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-12 sm:px-6 lg:px-8"
  >
    <Head title="Log in" />

    <div class="w-full max-w-md space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
          Log in to your account
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Enter your email and password below to log in
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
              required
              autofocus
              :tabindex="1"
              autocomplete="email"
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

          <!-- Password Field -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="password" class="block text-sm font-medium text-gray-700">
                Password
              </label>
              <a
                v-if="canResetPassword"
                :href="route('password.request')"
                :tabindex="5"
                class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200 focus:outline-none focus:underline"
              >
                Forgot password?
              </a>
            </div>
            <input
              id="password"
              type="password"
              required
              :tabindex="2"
              autocomplete="current-password"
              v-model="form.password"
              placeholder="Password"
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

          <!-- Remember Me Checkbox -->
          <div class="flex items-center">
            <input
              id="remember"
              type="checkbox"
              v-model="form.remember"
              :tabindex="3"
              class="h-4 w-4 rounded border-gray-300 text-blue-600 transition-colors duration-200 focus:ring-4 focus:ring-blue-500/10 focus:ring-offset-0 cursor-pointer"
            />
            <label
              for="remember"
              class="ml-3 text-sm font-medium text-gray-700 cursor-pointer select-none"
            >
              Remember me
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :tabindex="4"
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
            <span>{{ form.processing ? "Logging in..." : "Log in" }}</span>
          </button>
        </form>

        <!-- Sign Up Link -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <a
              :href="route('register')"
              :tabindex="5"
              class="font-semibold text-blue-600 hover:text-blue-700 transition-colors duration-200 focus:outline-none focus:underline"
            >
              Sign up
            </a>
          </p>
        </div>
      </div>

      <!-- Footer (Optional) -->
      <p class="text-center text-xs text-gray-500">
        By continuing, you agree to our Terms of Service and Privacy Policy
      </p>
    </div>
  </div>
</template>
