'use strict';
import gulp from 'gulp';
import dartSass from 'sass';
import gulpSass from 'gulp-sass';
import uglify from 'gulp-uglify';
import concat from 'gulp-concat';
import imagemin from 'gulp-image';
import fontmin from 'gulp-fontmin';
const sass = gulpSass(dartSass);

const THEME_NAME = 'the-theme';
const PLUGIN_NAME = 'cg-core';
const DIST_PATH_BASE = '../../';
const DIST_PATH_THEME = DIST_PATH_BASE + 'wp-content/themes/' + THEME_NAME + '/';
const DIST_PATH_PLUGIN = DIST_PATH_BASE + 'wp-content/plugins/' + PLUGIN_NAME + '/';
const SRC_PATH = '../';



gulp.task('wp-config', () =>
    gulp.src([SRC_PATH + 'wp-config.php'], {
        allowEmpty: true
    })
    .pipe(gulp.dest(DIST_PATH_BASE))
);


/*
* Theme Tasks
*/
gulp.task('theme-template', () =>
    gulp.src([SRC_PATH + 'themes/'+ THEME_NAME +'/template/**/*'])
    .pipe(gulp.dest(DIST_PATH_THEME))
);

gulp.task('theme-scss', () =>
    gulp.src([
        SRC_PATH + 'themes/'+ THEME_NAME +'/scss/**/*.scss',
        '!' + SRC_PATH + 'themes/'+ THEME_NAME +'/scss/inc/**/*.scss',
    ])
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    .pipe(gulp.dest(DIST_PATH_THEME + 'assets/css'))
);
 
gulp.task('theme-js', () =>
    gulp.src([
        SRC_PATH + 'themes/'+ THEME_NAME +'/js/**/*.js',
        '!' + SRC_PATH + 'themes/'+ THEME_NAME +'/js/inc/**/*.js',
    ])
    .pipe(uglify())
    .pipe(gulp.dest(DIST_PATH_THEME + 'assets/js'))
);

gulp.task('theme-js-includes', () =>
    gulp.src([
        SRC_PATH + 'themes/'+ THEME_NAME +'/js/inc/**/*.js'
    ])
    .pipe(uglify())
    .pipe(gulp.dest(DIST_PATH_THEME + 'assets/js'))
);

gulp.task('theme-images', () =>
    gulp.src([SRC_PATH + 'themes/'+ THEME_NAME +'/images/**/*'], {
        allowEmpty: true
    })
    .pipe(imagemin())
    .pipe(gulp.dest(DIST_PATH_THEME + 'assets/images'))
);

gulp.task('theme-fonts', () =>
    gulp.src([SRC_PATH + 'themes/'+ THEME_NAME +'/fonts/**/*.ttf'], {
        allowEmpty: true
    })
    .pipe(fontmin())
    .pipe(gulp.dest(DIST_PATH_THEME + 'assets/fonts'))
);



/*
* Plugin Tasks
*/
gulp.task('plugin-template', () =>
    gulp.src([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/template/**/*'])
    .pipe(gulp.dest(DIST_PATH_PLUGIN))
);

gulp.task('plugin-scss', () =>
    gulp.src([
        SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/scss/**/*.scss',
        '!' + SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/scss/inc/**/*.scss',
    ], {
        allowEmpty: true
    })
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    .pipe(gulp.dest(DIST_PATH_PLUGIN + 'assets/css'))
);
 
gulp.task('plugin-js', () =>
    gulp.src([
        SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/js/**/*.js',
        '!' + SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/js/inc/**/*.js',
    ], {
        allowEmpty: true
    })
    .pipe(uglify())
    .pipe(gulp.dest(DIST_PATH_PLUGIN + 'assets/js'))
);

gulp.task('plugin-js-includes', () =>
    gulp.src([
        SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/js/inc/**/*.js'
    ], {
        allowEmpty: true
    })
    .pipe(uglify())
    .pipe(gulp.dest(DIST_PATH_PLUGIN + 'assets/js'))
);

gulp.task('plugin-images', () =>
    gulp.src([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/images/**/*'], {
        allowEmpty: true
    })
    .pipe(imagemin())
    .pipe(gulp.dest(DIST_PATH_PLUGIN + 'assets/images'))
);

gulp.task('plugin-fonts', () =>
    gulp.src([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/fonts/**/*.ttf'], {
        allowEmpty: true
    })
    .pipe(fontmin())
    .pipe(gulp.dest(DIST_PATH_PLUGIN + 'assets/fonts'))
);


gulp.task('default', function () {
    
    /*
    * Watch Theme
    */
    gulp.watch([SRC_PATH + 'wp-config.php'], gulp.series('wp-config'));

    gulp.watch([SRC_PATH + 'themes/'+ THEME_NAME +'/template/**/*'], gulp.series('theme-template'));

    gulp.watch([SRC_PATH + 'themes/'+ THEME_NAME +'/scss/**/*.scss'], gulp.series('theme-scss'));
  
    gulp.watch([SRC_PATH + 'themes/'+ THEME_NAME +'/js/**/*.js'], gulp.series('theme-js'));
    
    gulp.watch([SRC_PATH + 'themes/'+ THEME_NAME +'/images/**/*'], gulp.series('theme-images'));
    
    gulp.watch([SRC_PATH + 'themes/'+ THEME_NAME +'/fonts/**/*.ttf'], gulp.series('theme-fonts'));
    
    
    /*
    * Watch Plugin
    */
    gulp.watch([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/template/**/*'], gulp.series('plugin-template'));
    
    gulp.watch([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/scss/**/*.scss'], gulp.series('plugin-scss'));
  
    gulp.watch([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/js/**/*.js'], gulp.series('plugin-js'));
    
    gulp.watch([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/images/**/*'], gulp.series('plugin-images'));
    
    gulp.watch([SRC_PATH + 'extensions/'+ PLUGIN_NAME +'/fonts/**/*.ttf'], gulp.series('plugin-fonts'));
    

});



gulp.task('prod', gulp.series(
    'wp-config',
    'theme-template',
    'theme-scss',
    'theme-js',
    'theme-js-includes',
    'theme-images',
    'theme-fonts',
    'plugin-template',
    'plugin-scss',
    'plugin-js',
    'plugin-js-includes',
    'plugin-images',
    'plugin-fonts'
));

gulp.task('prod-watch', gulp.series(
    'prod',
    'default',
));

/*gulp.task('optimize', gulp.series(
    'theme-images',
    'theme-fonts',
));

gulp.task('includes', gulp.series(
    'theme-js-includes',
));

gulp.task('prod', gulp.series(
    'wp-config',
    'includes',
    'optimize',
    'theme-template',
    'theme-scss',
    'theme-js',
));

gulp.task('prod-watch', gulp.series(
    'prod',
    'default',
));*/