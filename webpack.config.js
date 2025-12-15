const path                 = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin         = require('terser-webpack-plugin');
const CssMinimizerPlugin   = require('css-minimizer-webpack-plugin');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  mode:    isProduction ? 'production' : 'development',
  entry:   {
    app: ['./assets/js/app.js', './assets/css/app.scss']
  },
  output:  {
    path:     path.resolve(__dirname, 'public/build'),
    filename: isProduction ? 'js/[name].[contenthash:8].js' : 'js/[name].js',
    clean:    true
  },
  devtool: isProduction ? false : 'source-map',
  optimization: {
    minimize: isProduction,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          compress: { drop_console: true }
        }
      }),
      new CssMinimizerPlugin()
    ]
  },
  module:  {
    rules: [
      {
        test: /\.js$/,
        loader: 'babel-loader'
      },
      {
        test: /\.scss$/,
        use:  [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'sass-loader'
        ]
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: isProduction ? 'css/[name].[contenthash:8].css' : 'css/[name].css'
    })
  ]
};
