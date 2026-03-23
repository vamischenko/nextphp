import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: 'resources/assets/app.js'
      }
    }
  }
});
