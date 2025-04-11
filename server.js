const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');
const app = express();

// Usa el puerto dinámico asignado o el 3000 por defecto
const port = process.env.PORT || 3001;

// Proxy para redirigir solicitudes al servidor Apache
app.use('/', createProxyMiddleware({
  target: 'http://localhost', // URL base del servidor Apache
  changeOrigin: true,
  pathRewrite: {
    '^/': '/project1/', // Redirige todas las solicitudes al subdirectorio donde está tu proyecto
  },
}));

// Iniciar el servidor Express
app.listen(port, '0.0.0.0', () => {
  console.log(`Server running at http://0.0.0.0:${port}/`);
});
