require('custom-env').env(true);

var gulp = require('gulp'),
    terser = require('gulp-terser'),
    replace = require('gulp-replace'),
    sass = require('gulp-sass'),
    postcss = require('gulp-postcss'),
    cssnano = require('cssnano'),
    rename = require('gulp-rename'),
    plumber = require('gulp-plumber'),
    fs = require('fs');

function scripts() {
    return gulp
        .src('./src/widget.js')
        .pipe(plumber({
            errorHandler: function (err) {
                console.log(err.message);
                this.emit('end');
            }
        }))
        .pipe(replace('{{css}}', function(s) {
            var style = fs.readFileSync('./build/widget.min.css', 'utf8');
            return '<style>' + style + '</style>';
        }))

        .pipe(replace(' \t', ''))
        .pipe(replace('\t', ''))
        .pipe(replace('{{url}}', process.env.URL)) // Will need to find a way to update this based on location
        .pipe(terser())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./build'));
}

function styles() {
    return gulp.src('./src/widget.scss')
        .pipe(sass())
        .pipe(postcss([
            // autoprefixer(),
            cssnano()
        ]))
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./build'));
}

function watchFiles() {
    gulp.watch(['./src/*'],
    gulp.series(styles, scripts));
}

gulp.task('default', gulp.series( styles, scripts));
gulp.task('watch', watchFiles);

exports.styles = styles
exports.scripts = scripts
