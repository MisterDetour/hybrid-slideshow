(window.webpackJsonp_hybrid_slideshow=window.webpackJsonp_hybrid_slideshow||[]).push([[1],{4:function(e,r,t){}}]),function(e){function r(r){for(var o,l,s=r[0],u=r[1],c=r[2],d=0,f=[];d<s.length;d++)l=s[d],Object.prototype.hasOwnProperty.call(n,l)&&n[l]&&f.push(n[l][0]),n[l]=0;for(o in u)Object.prototype.hasOwnProperty.call(u,o)&&(e[o]=u[o]);for(p&&p(r);f.length;)f.shift()();return i.push.apply(i,c||[]),t()}function t(){for(var e,r=0;r<i.length;r++){for(var t=i[r],o=!0,s=1;s<t.length;s++){var u=t[s];0!==n[u]&&(o=!1)}o&&(i.splice(r--,1),e=l(l.s=t[0]))}return e}var o={},n={0:0},i=[];function l(r){if(o[r])return o[r].exports;var t=o[r]={i:r,l:!1,exports:{}};return e[r].call(t.exports,t,t.exports,l),t.l=!0,t.exports}l.m=e,l.c=o,l.d=function(e,r,t){l.o(e,r)||Object.defineProperty(e,r,{enumerable:!0,get:t})},l.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},l.t=function(e,r){if(1&r&&(e=l(e)),8&r)return e;if(4&r&&"object"==typeof e&&e&&e.__esModule)return e;var t=Object.create(null);if(l.r(t),Object.defineProperty(t,"default",{enumerable:!0,value:e}),2&r&&"string"!=typeof e)for(var o in e)l.d(t,o,function(r){return e[r]}.bind(null,o));return t},l.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return l.d(r,"a",r),r},l.o=function(e,r){return Object.prototype.hasOwnProperty.call(e,r)},l.p="";var s=window.webpackJsonp_hybrid_slideshow=window.webpackJsonp_hybrid_slideshow||[],u=s.push.bind(s);s.push=r,s=s.slice();for(var c=0;c<s.length;c++)r(s[c]);var p=u;i.push([5,1]),t()}([function(e,r){e.exports=window.wp.element},function(e,r){e.exports=window.wp.i18n},function(e,r){e.exports=window.wp.blockEditor},function(e,r){e.exports=window.wp.blocks},,function(e,r,t){"use strict";t.r(r);var o=t(3),n=(t(4),t(0)),i=t(1),l=t(2);Object(o.registerBlockType)("hybrid-vigor/hybrid-slideshow",{edit:function(){return Object(n.createElement)("p",Object(l.useBlockProps)(),Object(i.__)("Hybrid Slideshow – hello from the editor!","hybrid-slideshow"))},save:function(){return Object(n.createElement)("p",l.useBlockProps.save(),Object(i.__)("Hybrid Slideshow – hello from the saved content!","hybrid-slideshow"))}})}]);