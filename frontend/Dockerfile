# Multi-stage build for Vue.js frontend
FROM node:22-alpine AS build

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source code
COPY . .

# Ensure .env exists (will be copied by deploy script)
#RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Build the application
RUN npm run build-only

# Production stage with nginx
FROM nginx:alpine AS production

# Copy built assets from build stage
COPY --from=build /app/dist /usr/share/nginx/html

# Copy Leaflet marker icons to root for direct access
COPY --from=build /app/node_modules/leaflet/dist/images/marker-icon.png /usr/share/nginx/html/
COPY --from=build /app/node_modules/leaflet/dist/images/marker-icon-2x.png /usr/share/nginx/html/
COPY --from=build /app/node_modules/leaflet/dist/images/marker-shadow.png /usr/share/nginx/html/

# Optional: Copy custom logo if provided
# ARG CUSTOM_LOGO_PATH
# COPY ${CUSTOM_LOGO_PATH:-/app/public/logo.png} /usr/share/nginx/html/logo.png

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Create nginx user and set permissions
RUN chown -R nginx:nginx /usr/share/nginx/html && \
    chmod -R 755 /usr/share/nginx/html

# Expose port
EXPOSE 80

# Start nginx
CMD ["nginx", "-g", "daemon off;"]