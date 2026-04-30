// tailwind.config.js
import preset from './vendor/filament/support/tailwind.config.preset'

export default {
  presets: [preset],
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.php',
    './resources/**/*.vue',
    './resources/**/*.js',
    './vendor/filament/**/*.blade.php',
  ],
  theme: { extend: {} },
  plugins: [],
}
