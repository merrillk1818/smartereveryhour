/** @type {import('next').NextConfig} */
const nextConfig = {
    // Produces a self-contained .next/standalone server bundle so the
    // production Docker image doesn't need the full node_modules tree.
    output: 'standalone',
    // Pins the file-tracing root to this directory so a sibling lockfile
    // elsewhere in the repo isn't mistaken for a monorepo root.
    outputFileTracingRoot: __dirname,
};

module.exports = nextConfig;
