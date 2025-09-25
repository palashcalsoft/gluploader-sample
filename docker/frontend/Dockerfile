# --- base deps
FROM node:20-alpine AS deps
WORKDIR /app
COPY package*.json ./
# Prefer reproducible installs; fall back to npm install if lockfile is missing
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# --- build
FROM node:20-alpine AS build
WORKDIR /app
ENV NODE_ENV=production
COPY --from=deps /app/node_modules ./node_modules
COPY . .
RUN npm run build

# --- runtime
FROM node:20-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
# optional: non-root user
RUN addgroup -g 1001 nodejs && adduser -S nextjs -G nodejs
COPY --from=build /app/.next ./.next
COPY --from=build /app/public ./public
COPY package*.json ./
COPY --from=deps /app/node_modules ./node_modules
USER nextjs
EXPOSE 3000
CMD ["npm","run","start","--","-p","3000"]