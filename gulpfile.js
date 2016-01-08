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
	minifyCSS = require('gulp-minify-css'),
	flatten   = require('gulp-flatten'),
	inject    = require('gulp-inject'),
	rename    = require("gulp-rename");

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

gulp.task('app-less', function(){
	
	var sources = gulp.src([
			'!./resources/assets/less/imports.less',
			'./resources/assets/less/vars.less',
			'./resources/assets/less/mixins/**/*.less',
			'./resources/assets/less/**/*.less',
			'./angular/**/*.less'
		], {read: false})
		.pipe(unique())
		.pipe(print());
	
	return gulp.src('./resources/assets/less/imports.less')
		.pipe(inject(sources, {
			starttag: '/* inject:imports */',
			endtag: '/* endinject */',
			transform: function (path) {
				return '@import ".' + path + '";';
			}
		}))
		.pipe(plumber())
		.pipe(less())
		.pipe(minifyCSS())
		.pipe(rename('app.css'))
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
		//.pipe(uglify())
		.pipe(gulp.dest('./public/assets/js'));
		
});

gulp.task('angular-templates', function(){
	
	return gulp.src('./angular/**/*Template.html')
		.pipe(plumber())
		.pipe(flatten())
		.pipe(print())
		.pipe(gulp.dest('./public/views'));
	
});

gulp.task('angular-modals', function(){
	
	return gulp.src('./angular/modals/**/*Template.html')
		.pipe(plumber())
		.pipe(flatten())
		.pipe(print())
		.pipe(gulp.dest('./public/views/modals'));
	
});

gulp.task('angular-directives', function(){
	
	return gulp.src('./angular/**/*Directive.html')
		.pipe(plumber())
		.pipe(flatten())
		.pipe(print())
		.pipe(gulp.dest('./public/views/directives'));
	
});

/* Watch */

gulp.task('watch', function(){
	
	gulp.watch('./bower_components/**/*', ['bower-css', 'bower-js', 'bower-fonts']);
	
	gulp.watch('./resources/assets/**/*.less', ['app-less']);
	gulp.watch('./angular/**/*.less', ['app-less']);
	
	gulp.watch('./resources/assets/**/*.js', ['app-js']);
	gulp.watch('./resources/assets/**/*.{eot,svg,ttf,woff,woff2}', ['app-fonts']);
	
	gulp.watch('./angular/**/*.js', ['angular-js']);
	gulp.watch('./angular/**/*.html', ['angular-templates', 'angular-modals', 'angular-directives']);
	
});

gulp.task('default', ['bower-css', 'bower-js', 'bower-fonts', 'app-less', 'app-js', 'app-fonts', 'angular-js', 'angular-templates', 'angular-modals', 'angular-directives', 'watch']);