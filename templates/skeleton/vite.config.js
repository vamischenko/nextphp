import { defineConfig } from 'vite';

export default defineConfig({
  root: '.',
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
