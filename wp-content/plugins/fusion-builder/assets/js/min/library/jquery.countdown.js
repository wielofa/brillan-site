/*!
 * jQuery Countdown plugin v1.0
 * http://www.littlewebthings.com/projects/countdown/
 *
 * Copyright 2010, Vassilis Dourdounis
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
!function(s){s.fn.countDown=function(t){return config={},s.extend(config,t),diffSecs=this.setCountDown(config),config.onComplete&&s.data(s(this)[0],"callback",config.onComplete),config.omitWeeks&&s.data(s(this)[0],"omitWeeks",config.omitWeeks),s("#"+s(this).attr("id")+" .fusion-digit").html('<div class="top"></div><div class="bottom"></div>'),s(this).doCountDown(s(this).attr("id"),diffSecs,500),this},s.fn.stopCountDown=function(){clearTimeout(s.data(this[0],"timer"))},s.fn.startCountDown=function(){this.doCountDown(s(this).attr("id"),s.data(this[0],"diffSecs"),500)},s.fn.setCountDown=function(t){var e=new Date;t.targetDate?e=new Date(t.targetDate.month+"/"+t.targetDate.day+"/"+t.targetDate.year+" "+t.targetDate.hour+":"+t.targetDate.min+":"+t.targetDate.sec+(t.targetDate.utc?" UTC":"")):t.targetOffset&&(e.setFullYear(t.targetOffset.year+e.getFullYear()),e.setMonth(t.targetOffset.month+e.getMonth()),e.setDate(t.targetOffset.day+e.getDate()),e.setHours(t.targetOffset.hour+e.getHours()),e.setMinutes(t.targetOffset.min+e.getMinutes()),e.setSeconds(t.targetOffset.sec+e.getSeconds()));var i=new Date;if(t.gmtOffset){var a=60*t.gmtOffset*6e4,n=6e4*i.getTimezoneOffset();i=new Date(i.getTime()+a+n)}return diffSecs=Math.floor((e.valueOf()-i.valueOf())/1e3),s.data(this[0],"diffSecs",diffSecs),diffSecs},s.fn.doCountDown=function(i,a,n){$this=s("#"+i),a<=0&&(a=0,$this.data("timer")&&clearTimeout($this.data("timer"))),secs=a%60,mins=Math.floor(a/60)%60,hours=Math.floor(a/60/60)%24,1==$this.data("omitWeeks")?(days=Math.floor(a/60/60/24),weeks=Math.floor(a/60/60/24/7)):(days=Math.floor(a/60/60/24)%7,weeks=Math.floor(a/60/60/24/7)),days>99&&$this.find(".fusion-dash-days").find(".fusion-hundred-digit").css("display","inline-block"),days>999&&$this.find(".fusion-dash-days").find(".fusion-thousand-digit").css("display","inline-block"),weeks>99&&$this.find(".fusion-dash-weeks").find(".fusion-hundred-digit").css("display","inline-block"),$this.dashChangeTo(i,"fusion-dash-seconds",secs,n||800),$this.dashChangeTo(i,"fusion-dash-minutes",mins,n||1200),$this.dashChangeTo(i,"fusion-dash-hours",hours,n||1200),$this.dashChangeTo(i,"fusion-dash-days",days,n||1200),$this.dashChangeTo(i,"fusion-dash-weeks",weeks,n||1200),s.data($this[0],"diffSecs",a),a>0?(e=$this,t=setTimeout((function(){e.doCountDown(i,a-1)}),1e3),s.data(e[0],"timer",t)):(cb=s.data($this[0],"callback"))&&s.data($this[0],"callback")()},s.fn.dashChangeTo=function(t,e,i,a){$this=s("#"+t);for(var n=$this.find("."+e+" .fusion-digit").length-1;n>=0;n--){var o=i%10;i=(i-o)/10,$this.digitChangeTo("#"+$this.attr("id")+" ."+e+" .fusion-digit:eq("+n+")",o,a)}},s.fn.digitChangeTo=function(t,e,i){var a=s(t+" div.top"),n=s(t+" div.bottom");i||(i=800),a.html()!=e+""&&a.not(":animated").length&&(a.css({display:"none"}),a.html(e||"0").fadeOut(i,(function(){n.html(a.html()),n.css({display:"block",height:"auto"}),a.css({display:"none"})})))}}(jQuery);