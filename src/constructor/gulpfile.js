'use strict';
import gulp from 'gulp';
import imagemin from 'gulp-image';
import fontmin from 'gulp-fontmin';


gulp.task('images', () =>
    gulp.src(['../themes/the-theme/images/**/*'], {
        allowEmpty: true
    })
    .pipe(imagemin())
    .pipe(gulp.dest('../../wp-content/themes/the-theme/assets/images'))
);

gulp.task('fonts', () =>
    gulp.src(['../themes/the-theme/fonts/**/*.ttf'], {
        allowEmpty: true
    })
    .pipe(fontmin())
    .pipe(gulp.dest('../../wp-content/themes/the-theme/assets/fonts'))
);


gulp.task('default', gulp.series(
    'images',
    'fonts'
));