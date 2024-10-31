var gulp = require('gulp');
var sass = require('gulp-sass');
var cleanCSS = require('gulp-clean-css');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify');

const jsSrc = [
  'plugin-assets/js/admin.js',
  'plugin-assets/js/overlay-editor.js'
];

gulp.task('css', function () {
  return gulp.src('plugin-assets/css/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(gulp.dest('plugin-assets/css'));
});

gulp.task('scripts', function () {
  return gulp.src(jsSrc)
    .pipe(rename({suffix: '.min'}))
    .pipe(uglify())
    .pipe(gulp.dest('plugin-assets/js'));
});


gulp.task('watch', function() {
  gulp.watch(['plugin-assets/css/*.scss'], ['css']);
  gulp.watch(jsSrc, ['scripts']);
});

gulp.task('default', ['css', 'scripts']);
