/** @type {import('next').NextConfig} */
const nextConfig = {
  // Force Webpack watcher to use polling under WSL2 / mounted volumes
  webpackDevMiddleware: (config) => {
    config.watchOptions = {
      poll: 500,
      aggregateTimeout: 300,
      ignored: ['**/node_modules/**', '**/.git/**']
    };
    return config;
  },
  // Also set the server-side file watcher to polling
  experimental: {
    webpackMemoryOptimizations: true
  }
};

export default nextConfig;


