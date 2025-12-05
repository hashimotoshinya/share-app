# frontend/Dockerfile
FROM node:20-bullseye

WORKDIR /app

# oxc-parser 無効化 + npm安定化
ENV NUXT_NO_OXC_PARSER=1
ENV NODE_OPTIONS="--max-old-space-size=2048"
ENV CI=true

RUN npm install -g npm@11 && npm cache clean --force

COPY package*.json ./

# oxc-parser を完全にアンインストールしつつNuxtインストール
RUN rm -f package-lock.json \
    && npm install --omit=optional --no-audit --no-fund --ignore-scripts \
    && npm uninstall oxc-parser || true \
    && npm rebuild esbuild

COPY . .

# app起動
EXPOSE 3000
CMD ["npm", "run", "dev"]