var gulp = require('gulp'),

	plumber   = require('gulp-plumber'),
	print     = require('gulp-print'),
	bower     = require('main-bower-files'),
	
	merge2    = require('merge2'),
	filter    = require('gulp-filter'),
	concat    = require('gulp-concat'),
	unique    = require('gulp-unique-files'),
	
	less      = require('gulp-less'),
	uglify    = require('gulp-uglify'),
	minifyCSS = require('gulp-minify-css');

/* Vendor (Bower) */

gulp.task('bower-css', function(){
	
	return merge2(
			gulp.src(bower())
				.pipe(filter(['**/*.less']))
				.pipe(less()),
			gulp.src(bower())
				.pipe(filter(['**/*.css']))
		)
		.pipe(plumber())
		.pipe(print())
		.pipe(concat('vendor.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('./public/assets/css'));
	
});

gulp.task('bower-js', function(){
	
	return gulp.src(bower())
		.pipe(plumber())
		.pipe(filter(['**/*.js']))
		.pipe(print())
		.pipe(concat('vendor.js'))
		.pipe(uglify())
		.pipe(gulp.dest('./public/assets/js'));
	
});

gulp.task('bower-fonts', function(){
	
	return gulp.src(bower())
		.pipe(plumber())
		.pipe(filter(['**/*.eot', '**/*.svg', '**/*.ttf', '**/*.woff', '**/*.woff2']))
		.pipe(print())
		.pipe(gulp.dest('./public/assets/fonts'));
	
});

/* App (Assets) */

gulp.task('app-css', function(){
	
	return merge2(
			gulp.src('./resources/assets/**/*.less')
				.pipe(less()),
			gulp.src('./resources/assets/**/*.css')
		)
		.pipe(plumber())
		.pipe(print())
		.pipe(concat('app.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('./public/assets/css'));
	
});

gulp.task('app-js', function(){
	
	return gulp.src('./resources/assets/**/*.js')
		.pipe(plumber())
		.pipe(print())
		.pipe(concat('app.js'))
		.pipe(uglify())
		.pipe(gulp.dest('./public/assets/js'));
	
});

gulp.task('app-fonts', function(){
	
	return gulp.src('./resources/assets/**/*.{eot,svg,ttf,woff,woff2}')
		.pipe(plumber())
		.pipe(print())
		.pipe(gulp.dest('./public/assets/fonts'));
	
});

/* AngularJS */

gulp.task('angular-js', function(){
	
	return merge2(
			gulp.src('./angular/app.modules.js'),
			gulp.src('./angular/**/*.js')
		)
		.pipe(plumber())
		.pipe(unique())
		.pipe(print())
		.pipe(concat('angular.js'))
		.pipe(uglify())
		.pipe(gulp.dest('./public/assets/js'));
		
});

gulp.task('angular-templates', function(){
	
	return gulp.src('./resources/views/angular/**/*.html')
		.pipe(plumber())
		.pipe(print())
		.pipe(gulp.dest('./public/views'));
	
});

/* Watch */

gulp.task('watch', function(){
	
	gulp.watch('./bower_components/**/*', ['bower-css', 'bower-js', 'bower-fonts']);
	
	gulp.watch('./resources/assets/**/*.{css,less}', ['app-css']);
	gulp.watch('./resources/assets/**/*.js', ['app-js']);
	gulp.watch('./resources/assets/**/*.{eot,svg,ttf,woff,woff2}', ['app-fonts']);
	
	gulp.watch('./angular/**/*.js', ['angular-js']);
	gulp.watch('./resources/views/angular/**/*.html', ['angular-templates']);
	
});

gulp.task('default', ['bower-css', 'bower-js', 'bower-fonts', 'app-css', 'app-js', 'app-fonts', 'angular-js', 'angular-templates', 'watch']);