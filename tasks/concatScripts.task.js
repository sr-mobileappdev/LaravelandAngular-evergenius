var gulp = require('gulp')
var concat = require('gulp-concat')

var Elixir = require('laravel-elixir')

var minify = require('gulp-minify')

var cleanCss = require('gulp-clean-css')

var Task = Elixir.Task

Elixir.extend('concatScripts', function (scripts, dest) {
  new Task('concat-scripts', function () {
    return gulp.src(scripts)
      .pipe(concat(dest))
      /*.pipe(minify({
	ext:{
		min:'.js'
		},
		noSource: true
	}))*/
      .pipe(gulp.dest(Elixir.config.js.outputFolder))
  }).watch(scripts)
})

