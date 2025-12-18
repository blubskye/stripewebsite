/**
 * Webpack Configuration - Optimized for Node.js 24
 *
 * Node.js 24 features utilized:
 * - V8 13.6 engine with 15-30% performance improvements
 * - Enhanced async/await and Promise handling
 * - npm 11 with faster installs
 *
 * @see https://nodejs.org/en/blog/release/v24.0.0
 */
const path = require('path');
const os = require('os');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

const isProduction = process.env.NODE_ENV === 'production';
const cpuCount = os.cpus().length;

module.exports = {
  mode: isProduction ? 'production' : 'development',
  entry: {
    app: ['./assets/js/app.js', './assets/css/app.scss']
  },
  output: {
    path: path.resolve(__dirname, 'public/build'),
    filename: isProduction ? 'js/[name].[contenthash:8].js' : 'js/[name].js',
    chunkFilename: isProduction ? 'js/[name].[contenthash:8].chunk.js' : 'js/[name].chunk.js',
    assetModuleFilename: 'assets/[name].[contenthash:8][ext]',
    clean: true
  },
  devtool: isProduction ? false : 'source-map',

  // Node.js 24+ parallel processing
  parallelism: cpuCount,

  // Performance hints
  performance: {
    hints: isProduction ? 'warning' : false,
    maxAssetSize: 256000,
    maxEntrypointSize: 256000
  },

  // Filesystem caching for faster rebuilds
  cache: {
    type: 'filesystem',
    buildDependencies: {
      config: [__filename]
    },
    compression: 'gzip'
  },

  optimization: {
    minimize: isProduction,
    minimizer: [
      new TerserPlugin({
        parallel: cpuCount,
        terserOptions: {
          parse: {
            ecma: 2024
          },
          compress: {
            ecma: 2024,
            drop_console: isProduction,
            drop_debugger: isProduction,
            pure_funcs: isProduction ? ['console.log', 'console.info'] : [],
            passes: 2
          },
          mangle: {
            safari10: false
          },
          output: {
            ecma: 2024,
            comments: false
          }
        },
        extractComments: false
      }),
      new CssMinimizerPlugin({
        parallel: cpuCount,
        minify: CssMinimizerPlugin.lightningCssMinify
      })
    ],
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          chunks: 'all'
        }
      }
    },
    moduleIds: isProduction ? 'deterministic' : 'named',
    chunkIds: isProduction ? 'deterministic' : 'named',
    usedExports: true,
    sideEffects: true
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', {
                targets: {
                  browsers: [
                    'last 2 Chrome versions',
                    'last 2 Firefox versions',
                    'last 2 Safari versions',
                    'last 2 Edge versions'
                  ]
                },
                modules: false,
                bugfixes: true
              }]
            ],
            cacheDirectory: true,
            cacheCompression: false
          }
        }
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 2
            }
          },
          {
            loader: 'sass-loader',
            options: {
              api: 'modern-compiler',
              sassOptions: {
                quietDeps: true,
                silenceDeprecations: ['import']
              }
            }
          }
        ]
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/i,
        type: 'asset/resource',
        generator: {
          filename: 'fonts/[name].[contenthash:8][ext]'
        }
      },
      {
        test: /\.(png|svg|jpg|jpeg|gif|webp)$/i,
        type: 'asset',
        parser: {
          dataUrlCondition: {
            maxSize: 8192
          }
        },
        generator: {
          filename: 'images/[name].[contenthash:8][ext]'
        }
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: isProduction ? 'css/[name].[contenthash:8].css' : 'css/[name].css',
      chunkFilename: isProduction ? 'css/[name].[contenthash:8].chunk.css' : 'css/[name].chunk.css'
    })
  ],
  resolve: {
    extensions: ['.js', '.json'],
    symlinks: false,
    cacheWithContext: false
  },
  stats: isProduction ? 'normal' : 'minimal'
};
