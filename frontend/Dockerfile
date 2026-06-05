FROM node:22-alpine AS build

WORKDIR /app

RUN apk add --no-cache yarn

COPY frontend/package.json frontend/yarn.lock ./

RUN yarn install --frozen-lockfile --network-timeout 600000

COPY frontend/ .
COPY VERSION /app/VERSION

ARG VITE_API_URL_CLIENT
ARG VITE_API_URL_SERVER
ARG VITE_FRONTEND_URL
ARG VITE_STRIPE_PUBLISHABLE_KEY
ARG VITE_APP_NAME=Event Hosting
ARG VITE_PUSHER_APP_KEY
ARG VITE_PUSHER_HOST
ARG VITE_PUSHER_PORT=443
ARG VITE_PUSHER_SCHEME=https

ENV VITE_API_URL_CLIENT=${VITE_API_URL_CLIENT} \
    VITE_API_URL_SERVER=${VITE_API_URL_SERVER} \
    VITE_FRONTEND_URL=${VITE_FRONTEND_URL} \
    VITE_STRIPE_PUBLISHABLE_KEY=${VITE_STRIPE_PUBLISHABLE_KEY} \
    VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_PUSHER_APP_KEY=${VITE_PUSHER_APP_KEY} \
    VITE_PUSHER_HOST=${VITE_PUSHER_HOST} \
    VITE_PUSHER_PORT=${VITE_PUSHER_PORT} \
    VITE_PUSHER_SCHEME=${VITE_PUSHER_SCHEME}

RUN yarn build

FROM node:22-alpine AS production

WORKDIR /app

RUN addgroup -g 1001 -S nodejs && adduser -S nodejs -u 1001 -G nodejs

RUN apk add --no-cache yarn

COPY --from=build --chown=nodejs:nodejs /app/package.json /app/yarn.lock ./
COPY --from=build --chown=nodejs:nodejs /app/node_modules ./node_modules
COPY --from=build --chown=nodejs:nodejs /app/dist ./dist
COPY --from=build --chown=nodejs:nodejs /app/server.js ./server.js
COPY --from=build --chown=nodejs:nodejs /app/public ./public
COPY --from=build --chown=nodejs:nodejs /app/VERSION ./VERSION

USER nodejs

ENV NODE_ENV=production

EXPOSE 5678

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD wget -qO- http://127.0.0.1:5678/ > /dev/null 2>&1 || exit 1

CMD ["yarn", "start"]
