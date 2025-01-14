<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<META HTTP-EQUIV="imagetoolbar" CONTENT="no">
<title>JavaScript: DHTML Library, Drag &amp; Drop for Images and Layers</title>
<link rel="stylesheet" type="text/css" href="../newwalterzorn.css">
<style type="text/css">
<!--
#reltab {
  position:relative;
  border:1px solid #000099;
  background:#d3e3f9;
  layer-background-color:#d3e3f9;
}

#greylyr {
  position:absolute;
  left:6px;
  top:380px;
  width:122px;
  border:1px solid #000099;
  background:#eeeeee;
  layer-background-color:#eeeeee;
  overflow:hidden;
  visibility:hidden;
}

#ingreylyr {
  padding:3px;
}

#bluelyr {
  position:absolute;
  visibility:hidden;
  left:0px;
  top:2000px;
  width:460px;
  padding:20px;
  border:1px solid #000099;
  background:#d3e3f9;
  layer-background-color:#d3e3f9;
  visibility:hidden;
}
-->
</style>
<script type="text/javascript" src="../walterzorn.js"></script>
<script type="text/javascript">
<!--
if (!document.layers)
{
    document.write('<style type="text/css">#reldiv {position:relative;background-color:#ffffff;border:1px solid #000099;overflow:hidden;}'+
                   '#reldiv2 {position:relative;top:5px;background-color:#ffffff;border:1px solid #000099;overflow:hidden;}<\/style>');
}
//-->
</script>
</head>
<body bgcolor="#ffffff">
<script type="text/javascript" src="../scripts/wz_dragdrop.js"></script>

<a name="top"> </a>
  <table cellpadding="0" cellspacing="0" width="98%">
    <tr>
      <td>
        &nbsp;<a href="http://www.walterzorn.de/dragdrop/dragdrop.htm" target="_top"><img src="../images/germany.gif" align="middle" border="0" alt="German version" width="22" height="15"></a> <small><a href="http://www.walterzorn.de/dragdrop/dragdrop.htm" target="_top">German version</a></small>
      </td>
      <td align="right">
		<small>

          <!--a href="javascript:void(0)" onclick="dd.elements.cat.copies[dd.elements.cat.copies.length - 1].setHorizontal(!dd.elements.cat.copies[dd.elements.cat.copies.length - 1].horizontal);">Horz</a>
          <a href="javascript:void(0)" onclick="dd.elements.cat.copies[dd.elements.cat.copies.length - 1].setVertical(!dd.elements.cat.copies[dd.elements.cat.copies.length - 1].vertical);">Vert</a-->
          <!--a href="javascript:void(0)" onclick="dd.elements.cat.copy(2);">Cpy</a>
          <a href="javascript:void(0)" onclick="dd.elements.cat.copies[0].del();">Del 0</a>
          <a href="javascript:void(0)" onclick="for (var i = 0; i < dd.elements.length; i++) if (dd.elements[i].is_image) dd.elements[dd.n4? 'reldivn4' : 'reldiv'].addChild(dd.elements[i]);">-</a>
          <a href="javascript:void(0)" onclick="while (dd.elements[dd.n4? 'reldivn4' : 'reldiv'].children.length) dd.elements[dd.n4? 'reldivn4' : 'reldiv']._removeChild(0); var rl = dd.elements.greylyr, r = dd.elements.rabbit; rl.addChild(r); for (var i = 0; i < r.copies.length; i++) rl.addChild(r.copies[i]);">-</a>
          <a href="javascript:void(0)" onclick="var c = dd.elements.rabbit; c.moveTo(c.defx, c.defy); if (c.copies) for (var i = 0; i < c.copies.length; i++) c.copies[i].moveTo(c.copies[i].defx, c.copies[i].defy);">-</a-->
		</small>
	  </td>
    </tr>
  </table>
  <center>
    <table>
      <tr>

        <td id="op56check" align="center"><a href="../index.htm"><img name="logo" border="0" src="../images/logo.gif" alt="Home" width="278" height="30"></a></td>
      </tr>
    </table>
    <table cellspacing="12">
      <tr>
        <td><a href="../index.htm">Home</a></td>
        <td><a href="../jsgraphics/jsgraphics_e.htm">VectorGraphics-Library</a></td>
        <td><a href="../tooltip/tooltip_e.htm">Tooltips</a></td>

        <td><a href="../grapher/grapher_e.htm">Function-Grapher</a></td>
      </tr>
    </table>
    <br>
  </center>
  <center>
    <table cellpadding="4">
      <tr align="left">

        <td valign="top" align="right">
          <img src="../images/transparentpixel.gif" alt="DHTML JavaScript Drag and Drop for Layers and Images" width="1" height="30">
          <br>
          <img src="../images/transparentpixel.gif" alt="DHTML JavaScript Drag and Drop for Layers and Images" width="20" height="1"><img name="cat" src="../images/dragdrop/ddcat.jpg" width="129" height="154" alt="DragnDrop Image">
          <br>&nbsp;<br>&nbsp;<br>

          <table cellpadding="0" cellspacing="3">
            <tr>
              <td>

                &nbsp;<br>


<script type="text/javascript"><!--
google_ad_client = "pub-4121946824037604";
google_ad_width = 120;
google_ad_height = 600;
google_ad_format = "120x600_as";
google_ad_type = "text";
google_ad_channel ="";
google_color_border = "FFFFFF";
google_color_bg = "FFFFFF";
google_color_link = "3355CC";
google_color_url = "008000";
google_color_text = "000000";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

                <br>&nbsp;<br>&nbsp;
              </td>
              <td width="1" bgcolor="#000099"><img src="../images/transparentpixel.gif" width="1" height="1" alt="JavaScript Drag and Drop for Layers and Images"></td>
            </tr>

          </table>
        </td>
        <td>
          <table cellpadding="4">
            <tr>
              <td>
                <big><b>JavaScript: DHTML API,<br>
                Drag &amp; Drop for Images and Layers</b></big><br>

                <small>JavaScript Library. Developer: Walter Zorn</small>
              </td>
              <td rowspan="4" valign="top" align="right">
                &nbsp;<br>&nbsp;<br>
                <table cellpadding="0" cellspacing="3" width="130">
                  <tr>
                    <td width="1" bgcolor="#000099"><img name="sepline1" src="../images/transparentpixel.gif" width="1" height="1" alt="JavaScript Drag and Drop for Layers and Images"></td>
                    <td>

                      <small>
                        &nbsp;<br>
                        <a href="#browser">Cross Browser</a>
                        <br>&nbsp;<br>
                        <a href="#config">How&nbsp;to&nbsp;include</a>
                        <br>&nbsp;<br>
                        <a href="#addons">DHTML API</a>

                        <br>&nbsp;<br>
                        <a href="#download">Download</a>
                        <br>&nbsp;
                      </small>
                    </td>
                  </tr>
                </table>
              </td>

            </tr>
            <tr>
              <td>
                A<sup>&nbsp;</sup>Cross-browser JavaScript DHTML Library which adds Drag Drop functionality and extended DHTML capabilities to layers and to any desired image, even those integrated into the text <img name="smile1" src="../images/smile.gif" width="11" height="11" alt="Drag &amp; Drop Image"> flow.
                <div id="reldiv"><ilayer name="reldivn4" bgcolor="#eeeeee"><div>
                  To convert images or layers into draggable DHTML items, simply pass their names/IDs to the library's main function 'SET_DHTML()'.
                </div></ilayer></div>
                Optional <a href="commands_e.htm">commands</a> allow to change and customize the behaviour of Drag&amp;Drop items in multiple ways.
                For example, you can limit how far a drag&amp;drop item can be moved, specify the cursor, or multiply drag'ndrop images.
                The DHTML API of this DHTML Drag&amp;Drop&nbsp;JavaScript is easily understandable.
                It provides methods to <a class="code" href="javascript:void(0)" onclick=";if(window.dd &amp;&amp; dd.elements){dd.elements.cat.maximizeZ();dd.elements.cat.moveTo(dd.elements.cat.x+20, dd.elements.cat.y+20);}return false;">moveTo()</a>,
                <a class="code" href="javascript:void(0)" onclick=";if(window.dd &amp;&amp; dd.elements){dd.elements.cat.maximizeZ();dd.elements.cat.resizeTo((dd.elements.cat.w > 195)? 129 : 258, (dd.elements.cat.h > 221)? 154 : 308);}return false;">resizeTo()</a>,
                <a class="code" href="javascript:void(0);" onclick=";if(window.dd &amp;&amp; dd.elements)dd.elements.cat.hide();return false;">hide()</a> and
                <a class="code" href="javascript:void(0);" onclick=";if(window.dd &amp;&amp; dd.elements) dd.elements.cat.show();return false;">show()</a> again drag&nbsp;n'&nbsp;drop elements,
                or to <a class="code" href="javascript:void(0)" onclick="if (window.dd &amp;&amp; dd.elements) dd.elements.cat.copy();">copy()</a> images right within the textflow of your page dynamically,
                and <a class="code" href="javascript:void(0)" onclick=";if(window.dd &amp;&amp; dd.elements){var reldiv = dd.n4? dd.elements.reldivn4 : dd.elements.reldiv; reldiv.setBgColor((reldiv.bgColor == '#d3e6ff')? '#ffcccc' : (reldiv.bgColor == '#ffcccc')? '#cceecc' : (reldiv.bgColor == '#cceecc')? (dd.n4? '#eeeeee' : '#ffffff') : '#d3e6ff');}">much</a> more.
                Each DHTML item has properties such as x, y, w, h, z, defx, defy, defw, defh, defz (co-ordinates, size, z-index, and their initial default values, respectively) plus multiple others,
                which you can read whenever desired.
                For instance, to store the current position of a drag&amp;drop item, you might write its x and y properties into a &lt;input&nbsp;type=&quot;hidden&quot;&gt; form element, from where you could transmit them to the server.
                For more details, see the <a href="api_e.htm">DHTML API</a> and  <a href="commands_e.htm">commands</a> reference.
                <br>

                <div id="reldiv2"><ilayer name="reldiv2n4" bgcolor="#eeeeee"><div>
                The idea behind wz_dragdrop.js was not merely to drag around some layers or images on a page in IE only,
                but also to be a cross-browser clientside API for interactive webpages and webbased applications.
        	    </div></ilayer></div>
              </td>
            </tr>
            <tr>
              <td>
                <big>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br><b>Resize Instead of Drag?</b></big>
                <br>

                Holding the &lt;Shift&gt; key <img name="tryit" src="../images/tryit.gif" width="50" height="17" alt="Drag n Drop DHTML JavaScript f&uuml;r Bilder und Layer"> while dragging an element lets wz_dragdrop.js work in Resize Mode rather than in Drag Mode.
                This functionality can be easily activated by passing to SET_DHTML() the
                <a href="#addons">command</a>
                RESIZABLE, or SCALABLE to preserve the width/height ratio of the element.
                These commands may be applied, at your option, either to certain or to all of the drag drop elements.
                By the way, on these pages the RESIZABLE functionality has been activated globally.
                <br clear="all">
                <img name="bikegoeteborg" src="../images/dragdrop/northcapetrip_goeteborg.jpg" alt="On my first bicycle trip to Northcape" title="On my first bicycle trip to Northcape" align="left" border="0" height="444" width="426">
                <br clear="all">
                <small><i>On my 8 megameters bicycle trip through Norway, Northern-Finland and Sweden<br>(Hamburg-Northcape-Kirkenes-Trelleborg-Munich)</i></small>

              </td>
            </tr>
            <tr>
              <td>
                <big>&nbsp;<br>&nbsp;<br><a name="browser"> </a>&nbsp;<br>&nbsp;<br><b>Cross-Browser Functionality</b></big>
                <br>
                Allmost all browsers (see the following list), except of a few rarely used ones, should be able to interpret this DHTML&nbsp;Drag&nbsp;'n&nbsp;Drop&nbsp;JavaScript.
                <br>&nbsp;<br>

                <img align="right" name="leop" src="../images/dragdrop/ddleop.jpg" alt="" width="300" height="205" hspace="4">
                Linux:<br>
                Browsers based on the Gecko-engine (Mozilla, Netscape&nbsp;6+, Galeon...), Konqueror 3.2+, Netscape&nbsp;4, Opera&nbsp;5+.
                <br>&nbsp;<br>
                Windows:<br>
                Gecko browsers (Mozilla, Netscape&nbsp;6+, Phoenix...), Netscape&nbsp;4, Opera&nbsp;5, 6 and 7, Internet Explorer&nbsp;4+.
                <br>&nbsp;<br>

                Other Systems:<br>
                Mac Safari works fine.
                Assuming that their behavior is essentially the same as with their Linux and Windows counterparts, I've given Gecko browsers, Netscape&nbsp;4 and Opera&nbsp;5+ unlimited access to execute the Drag&nbsp;and&nbsp;Drop JavaScript.
              </td>
            </tr>
            <tr>
              <td>
                <big>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br><b>Window Resize Events</b></big>

                <br>
                wz_dragdrop.js automatically re-integrates images and relatively positioned layers into the logical structure of the page.
                That is, back into the page flow even if line-breaks, table-sizes etc. have changed.
                Items that have previously been dragged keep their relative offset from their - also recalculated - default location within the page flow (accessible, by the way, through the defx and defy properties of each item).
                That means: A user won't break the API if they decide to resize their browser window.
                <br>
                <img name="skitour" src="../images/skitour_suedtirol_2.jpg" alt="" width="550" height="298">
                <br>
                <small><i>Ski touring in South Tyrol</i></small>
              </td>
              <td rowspan="1" align="right">
                <table cellpadding="0" cellspacing="3" width="130">

                  <tr>
                    <td width="1" bgcolor="#000099"><img src="../images/transparentpixel.gif" width="1" height="1" alt="DHTML Drag &amp; Drop JavaScript for Images and Layers"></td>
                    <td>
                      <small>
                        &nbsp;<br>
                        <a href="#top">Top of page</a>
                        <br>&nbsp;<br>
                        <a href="#browser">Cross Browser</a>

                        <br>&nbsp;<br>
                        <a href="#config">How&nbsp;to&nbsp;include</a>
                        <br>&nbsp;<br>
                        <a href="#addons">DHTML API</a>
                        <br>&nbsp;<br>
                        <a href="#download">Download</a>

                        <br>&nbsp;
                      </small>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td>

                <big>&nbsp;<br>&nbsp;<br><a name="config"> </a>&nbsp;<br>&nbsp;<br><b>How to include the Drag &amp; Drop Script</b></big>
                <br>
                <b><big>1.</big> HTML File: Names for Drag&amp;Drop elements</b>
                <br>
                <b>Images:</b>

                Each of the images to be set draggable requires a unique name, as for instance:
                <img align="left" name="chameleon" src="../images/dragdrop/ddcham.jpg" width="155" height="95" alt="JavaScript, DHTML: Drag&amp;Drop Images">
                &lt;img&nbsp;name=&quot;name1&quot;&nbsp;src=&quot;someImg.jpg&quot; width=&quot;240&quot; height=&quot;135&quot;&gt;<br>
                Width and height attributes are mandatory and should be
                absolute numbers like width=&quot;240&quot;, rather than relative ones like width=&quot;33%&quot;.
                <b>Layers:</b>

                Each one requires a unique ID and, contrary to images, must be positioned relatively or absolutely:<br>
                &lt;div&nbsp;id=&quot;name2&quot;&nbsp;style=&quot;position:absolute;...&quot;&gt;Content&lt;/div&gt;.
              </td>
              <td rowspan="5" align="right">
                <table cellpadding="0" cellspacing="3" width="130">

                  <tr>
                    <td width="1" bgcolor="#000099"><img src="../images/transparentpixel.gif" width="1" height="1" alt=""></td>
                    <td>
                      <small>
                        &nbsp;<br>
                        <a href="#top">Top of page</a>
                        <br>&nbsp;<br>
                        <a href="#browser">Cross Browser</a>

                        <br>&nbsp;<br>
                        <a href="#config">How&nbsp;to&nbsp;include</a>
                        <br>&nbsp;<br>
                        <a href="#addons">DHTML API</a>
                        <br>&nbsp;<br>
                        <a href="#download">Download</a>

                        <br>&nbsp;
                      </small>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td>

                <b>&nbsp;<br><big>2.</big> Insert pieces of code into HTML file</b>
                <br>
                Insert the following lines inside the &lt;body&gt; section of your html file.
                <br>
                This one immediately <b style="color:red">after (not before!!!) the opening &lt;body&gt; tag</b>:
              </td>

            </tr>
            <tr>
              <td>
                <table bgcolor="#000099" border="0" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td>
                      <table border="0" cellpadding="1" cellspacing="1" width="100%">
                        <tr>
                          <td bgcolor="#eeeeee">

<tt><font size="2" style="font-size:12px;">
&lt;script type=&quot;text/javascript&quot; src=&quot;wz_dragdrop.js&quot;&gt;&lt;/script&gt;
</font></tt>
                          </td>
                        </tr>
                      </table>
                    </td>

                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td>
                And this one directly before the closing &lt;/body&gt; tag:
              </td>

            </tr>
            <tr>
              <td>
                <table bgcolor="#000099" border="0" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td>
                      <table border="0" cellpadding="1" cellspacing="1" width="100%">
                        <tr>
                          <td bgcolor="#eeeeee">

<tt><font size="2" style="font-size:12px;">
&lt;script type=&quot;text/javascript&quot;&gt;<br>
&lt;!--<br>
 <br>
SET_DHTML(&quot;name1&quot;,&nbsp;&quot;name2&quot;,&nbsp;&quot;anotherLayer&quot;,&nbsp;&quot;lastImage&quot;);<br>

 <br>
//--&gt;<br>
&lt;/script&gt;
</font></tt>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                </table>
              </td>
            </tr>
            <tr>
              <td>
                As evident from this example, simply the names of the drag-drop elements must be passed to SET_DHTML( ), each in quotation marks and with commas separated from each other.
                Sequence of names may be arbitrary.
              </td>
              <td rowspan="3" align="right">
                <table cellpadding="0" cellspacing="3" width="130">

                  <tr>
                    <td width="1" bgcolor="#000099"><img src="../images/transparentpixel.gif" width="1" height="1" alt=""></td>
                    <td>
                      <small>
                        &nbsp;<br>
                        <a href="#top">Top of page</a>
                        <br>&nbsp;<br>
                        <a href="#browser">Cross Browser</a>

                        <br>&nbsp;<br>
                        <a href="#config">How&nbsp;to&nbsp;include</a>
                        <br>&nbsp;<br>
                        <a href="#addons">DHTML API</a>
                        <br>&nbsp;<br>
                        <a href="#download">Download</a>

                        <br>&nbsp;
                      </small>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td>

                <table align="right" border="0" cellpadding="0" cellspacing="0" width="265">
                  <tr>
                    <td><img name="northcape" align="right" src="../images/dragdrop/nothcapetrip2_northcape.jpg" alt="On my second bicycle trip to the Northcape" title="On my second bicycle trip to the Northcape" border="0" height="410" width="265"></td>
                  </tr>
                  <tr>
                    <td><small><i>At the Northcape, on my second Scandinavia bicycle trip</i></small></td>
                  </tr>
                </table>

                &nbsp;<br>&nbsp;<br>
                <b><big>3.</big> Save script file</b>
                <br>
                <a href="#download">Download the JavaScript Drag Drop library</a>, unzipp it and save it as wz_dragdrop.js into the same directory as the html file.
                Or adapt the path src=&quot;wz_dragdrop.js&quot; within the above code piece if you prefer to save the library to a different directory.
                <br>&nbsp;<br>&nbsp;<br><b><big>4.</big>Save transparent 1x1 pixel GIF</b>

                <br>
                transparentpixel.gif, coming with the download file wz_dragdrop.zip, is required by Netscape&nbsp;4 to create spacers at the default places of the drag drop images.
                transparentpixel.gif should be saved into the same directory as the html file.
                <br clear="all">
                <br>&nbsp;<br>&nbsp;<br><b><big>5.</big> Doesn't work?</b>
                <br>
                <a name="notwork"> </a>Then everything should function. If not:
                <br>

                <b>a)</b> Check the HTML and CSS syntax of your page, preferably with the W3C-Online-Validator on <a href="http://validator.w3.org/" target="_blank">http://validator.w3.org/</a>.
                <br>
                <b>b)</b> Make sure that you've carefully followed <a href="#config">steps 1. through 4</a> (by far the most frequent reason for problems).
                <br>
                <b>c)</b>

                Check your own JavaScript code for logical errors and syntax errors, the latter e.g. by looking at your browser's JavaScript error console.
                <br>
                <b>d)</b>
                Occasionally, problems arise with XHTML DTD pages, since the XHTML specification - annoyingly - doesn't permit document.write().
                Then it's inevitable to switch your page to another DTD, preferably HTML4.01.
                <br>
                <b>Don't claim you've found a bug</b> - unless you have everything checked carefully and you're very sure.
                Feedback is anyway welcome.
              </td>
            </tr>
            <tr>

              <td>
                <big>&nbsp;<br><a name="addons"> </a>&nbsp;<br>
                <img align="right" name="slidercanvas" src="../images/transparent_300_120.gif" width="300" height="120" alt="">
                &nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>
                <b>DHTML API</b></big>
                <br>
                <b>Clone or multiply an image?</b>

                <br>
                <img align="left" name="muckl" src="../images/dragdrop/muckl.jpg" alt="Drag Drop Image" width="100" height="130">
                Or change the cursor?
                <img align="right" name="thumb" src="../images/dragdrop/sliderthumb.gif" width="22" height="35" alt="">
                <img align="right" name="track" src="../images/dragdrop/track.gif" width="94" height="35" alt="">
                Or convert an image into a slider with stops?
                There are several commands available which change, if passed to SET_DHTML(), the behavior of JavaScript Drag n' Drop items.
                <br>&nbsp;<br>
                <center>
                  <a class="code" href="javascript:void(0)" onclick="var muckl; if (window.dd &amp;&amp; dd.elements) (muckl = dd.elements.muckl).swapImage((muckl.src != muckl.defsrc)? '../images/dragdrop/muckl.jpg' : '../images/dragdrop/rex.jpg', true); return false;">swapImage()</a>

                </center>
                <b>Scripting Drag Drop Elements</b>
                <br>
                For example, reading the current coordinates of a certain drag-drop item and then calling its moveTo() method offers an easy way to animate images and layers.
                Moreover, you may even convert a simple web page, just containing a few images, into a graphical user interface application, working with the coordinates of the elements (as demonstated with the slider example above)...
                The DHTML&nbsp;Drag&nbsp;Drop&nbsp;Library provides an easily understandable, user-friendly interface to the properties and methods of the drag-drop&nbsp;elements.
                Using this library as DHTML API you haven't to bother with browser or DOM detection - all this is performed by the library.
                For instance, the line
                <br>
                <tt>dd.elements.image2.moveTo(300, 200);</tt>

                <br>
                would reliably move &quot;image2&quot; to the absolute coordinates (300, 200) in any browser that can interpret the library,
                as reliably as the expression <tt>dd.elements.image2.x</tt> will retrieve the current X coordinate of the image.
                <br>
                <img name="low" src="../images/dragdrop/low.gif" alt="Drag&amp;Drop Image" width="62" height="25"><img name="upleft" src="../images/dragdrop/upleft.gif" alt="Drag &amp; Drop Image" width="97" height="41">
                <br>
                <table>

				  <tr>
				    <td>
                      <big>&middot;</big>
					</td>
					<td>
					  <big><a href="commands_e.htm">Optional Commands</a>:</big> Demonstration and Reference
					</td>
				  </tr>

				  <tr>
				    <td>
                      <big>&middot;</big>
					</td>
					<td>
					  <big><a href="api_e.htm">DHTML API, Scripting Drag Drop Items</a>:</big> Demo and Reference
                    </td>
			      </tr>

				  <tr>
				    <td>
                      <big>&middot;</big>
					</td>
					<td>
					  <big><a href="demos/demos.htm">Examples</a></big> using the DHTML API<a name="download"> </a>
                    </td>

			      </tr>
				  <tr>
				    <td>
					 <big>&middot;</big>
					</td>
					<td>
					  <big><a href="history_e.htm">History of Updates</a></big> (read this if you aren't sure about the benefits from an update)
				    </td>

				  </tr>
				</table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr align="left">
        <td align="right">

          <table cellpadding="0" cellspacing="3">
            <tr>
              <td>
                &nbsp;<br>

<script type="text/javascript"><!--
google_ad_client = "pub-4121946824037604";
google_ad_width = 120;
google_ad_height = 90;
google_ad_format = "120x90_0ads_al";
google_ad_channel ="";
google_color_border = "FFFFFF";
google_color_bg = "FFFFFF";
google_color_link = "3355CC";
google_color_text = "000000";
google_color_url = "008000";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

                <br>&nbsp;<br>&nbsp;

              </td>
              <td width="1" bgcolor="#000099"><img src="../images/transparentpixel.gif" width="1" height="1" alt="JavaScript Drag and Drop for Layers and Images"></td>
            </tr>
          </table>
        </td>
        <td>
          <table cellpadding="4">
            <tr>
              <td>

                &nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>
                <img align="right" name="koala" src="../images/dragdrop/ddkoala.jpg" alt="Drag&amp;Drop Image" width="160" height="213">
                <b>&nbsp;<br>&nbsp;<br>
                <img name="redarrow" src="../images/redarrow.gif" width="15" height="13" alt="" align="middle"><big>Download</big></b>
                <br>
                wz_dragdrop.js 4.91, published under the <b>LGPL</b>:<br>
                <a style="text-decoration:underline;" href="../scripts/wz_dragdrop.zip">wz_dragdrop.zip</a> (12 KB)
                <br clear="all">&nbsp;<br>&nbsp;<br>&nbsp;<br>

                <b><big>Donations</big></b>
                <br>
                for the very many hours of development, and for the costs of hosting this website, are welcome, of course:<br> <br>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" align="middle" name="submit" alt="Zahlen Sie mit PayPal - schnell, kostenlos und sicher!">
<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
(Donation - &euro;)
</form>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" align="middle" name="submit" alt="Zahlen Sie mit PayPal - schnell, kostenlos und sicher!">
<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
(Donation - US-Dollar)
</form>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" align="middle" name="submit" alt="Zahlen Sie mit PayPal - schnell, kostenlos und sicher!">
<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
(Donation - Australian Dollar)
</form>
                <br clear="all">
                <br>

                <br>
                <div id="reltab">
                  <table>
                    <tr>
                      <td bgcolor="#d3e3f9">
                        <b>Attention:</b>
                        <br>&nbsp;<br>
                        <b>1.)</b> If you use wz_dragdrop.js for applications or for extended scripting, and are going to upgrade from a version prior to ver. 3.4, please consider:
                        With version 3.4 the array that contains the drag drop elements has been renamed from <tt><b>dd.element</b></tt> to <tt><b>dd.elements</b></tt>&nbsp;&nbsp;.
                        You must change your code accordingly in case you've used any <tt><b>dd.element</b></tt> statement (see also <a href="api_e.htm">Scripting Drag Drop elements, DHTML API</a>).
                        <br>&nbsp;<br>

                        <b>2.)</b> With version 4.0, the library has been renamed from dragdrop.js to <b>wz_dragdrop.js</b>.
                        <br>&nbsp;<br>
                        <b>3.)</b> With version 4.2, the method <tt><b>activate()</b></tt> has been renamed to <tt><b>maximizeZ()</b></tt>.
                        <br>&nbsp;<br>

                        <b>4.)</b> With version 4.31, the command <tt><b>MULTIPLY</b></tt> has been renamed to <tt><b>COPY</b></tt>, and its usage has been changed as follows:
                        To create 12 copies of an image named &quot;imageName&quot;, you must write <tt><b>&quot;imageName&quot;+COPY+12</b></tt> instead of <tt><b>&quot;imageName&quot;+MULTIPLY12</b></tt>

                        <br>&nbsp;<br>
                        <b>5.)</b> With version 4.41, <b>SET_DRAGGABLE()</b> has been renamed to <b>SET_DHTML()</b> and <b>ADD_DRAGGABLE()</b> to <b>ADD_DHTML()</b>.
                      </td>

                    </tr>
                  </table>
                </div>
              </td>
              <td rowspan="1" align="right" valign="bottom">
                <table cellpadding="0" cellspacing="3" width="130">
                  <tr>
                    <td width="1" bgcolor="#000099"><img src="../images/transparentpixel.gif" width="1" height="1" alt=""></td>
                    <td>

                      <small>
                        &nbsp;<br>
                        <a href="#top">Top of page</a>
                        <br>&nbsp;<br>
                        <a href="#browser">Cross Browser</a>
                        <br>&nbsp;<br>
                        <a href="#config">How&nbsp;to&nbsp;include</a>

                        <br>&nbsp;<br>
                        <a href="#addons">DHTML API</a>
                        <br>&nbsp;<br>
                        <a href="#download">Download</a>
                        <br>&nbsp;
                      </small>
                    </td>
                  </tr>

                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <br>&nbsp;<br>
    <hr width="95%" size="1" noshade>

    <table cellpadding="20">
      <tr>
        <td>
          <small><a href="javascript:void(0)" onmouseover="this.href=Ml()" onclick="this.href=Ml()">Walter Zorn</a>, Munich</small>
          <br>
          &nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br><img name="counter" alt="" src="http://cgicounter.puretec.de/cgi-bin/cnt?clsid=6618c08714b49c97228db9da7cdaa38d1"> <small>visitors on www.walterzorn.com since 1. 1. 2009</small>
        </td>

      </tr>
    </table>
  </center>
<div id="greylyr">
  <a name="demolyr"> </a>
  <div id="ingreylyr">
    <center><img name="button" src="../images/dragdrop/button_outset.gif" alt="Drag Drop JavaScript" width="60" height="17"></center>
    <br>
    <small>wz_dragdrop.js provides extended DHTML capabilities...
    <br>&nbsp;<br>

    ...even for images nested into layers (DIV elements).
    <br>&nbsp;<br>
    For example, just a few simple JavaScript lines are necessary to implement the functionalities of this button.
    <br>&nbsp;<br>
    <a href="#flexlyr">Jump to another demo-layer.</a><br>&nbsp;<br>&nbsp;
    </small>
  </div>
</div>
<div id="bluelyr">
  <a name="flexlyr"> </a>

  <center>
    Check out if links in drag&amp;drop layers work correctly.
    And if they connect to their targets correctly, even after the layers have been dragged around...
    <small><br>&nbsp;<br><a href="#demolyr">Back to first layer</a></small>
  </center>
</div>
<table><tr><td height="150"><br></td></tr></table>
<script type="text/javascript">
<!--

SET_DHTML(CURSOR_MOVE, RESIZABLE, NO_ALT, SCROLL, "bikegoeteborg"+TRANSPARENT, "bluelyr"+TRANSPARENT, "button"+VERTICAL+HORIZONTAL+CURSOR_DEFAULT, "cat"+TRANSPARENT, "chameleon", "counter", "greylyr"+TRANSPARENT, "koala", "leop", "low", "muckl"+TRANSPARENT+COPY+4, "northcape"+TRANSPARENT, "redarrow", "reldiv", "reldivn4", "reldiv2"+TRANSPARENT, "reldiv2n4"+TRANSPARENT, "reltab", "sepline1"+NO_DRAG, "skitour"+TRANSPARENT, "slidercanvas", "smile1"+SCALABLE, "thumb"+HORIZONTAL+MAXOFFLEFT+45+MAXOFFRIGHT+45, "track"+NO_DRAG, "tryit", "upleft");

// Kreuzotter-vs-Uplefts Animation, demonstrating how to use the API of the DragDrop-Library
function ANIM()
{
    if (!dd.obj || !dd.op6)
    {
        if (dd.elements.low.z <= dd.elements.upleft.z)
            dd.elements.upleft.setZ(dd.elements.low.z - 1);

        dd.elements.upleft.moveTo(
            dd.elements.upleft.x <= -dd.elements.upleft.w?
                iW - dd.elements.upleft.w - 20 :
                (dd.elements.upleft.x - 3),
            (dd.obj==dd.elements.upleft)?
                dd.elements.upleft.y :
                (dd.elements.low.y - dh)
        );

        dd.elements.low.moveTo(
            dd.elements.low.x <= -dd.elements.low.w?
                iW-dd.elements.low.w-20 :
                (dd.elements.low.x-5),
            dd.elements.upleft.y + dh
        );
    }
    setTimeout("ANIM()", dd.kq? 200 : 100);
}


// override funcs defined within the Lib
function my_PickFunc()
{
    if (dd.obj.name == 'button') dd.obj.swapImage(insetbutton.src);
    if (!dd.obj.name.indexOf('muckl') && dd.obj.x == dd.obj.defx)
    {
        if (dd.elements.muckl.copies.length < 80) dd.elements.muckl.copy();
    }
}

function my_DragFunc()
{
    if (dd.obj.name == 'thumb')
    {
        var red = parseInt((dd.elements.thumb.x-dd.elements.thumb.defx)*255/45),
        blue = -red,
        green = (255-Math.abs(red)).toString(16);
        red = (red > 0? red : 0).toString(16);
        blue = (blue > 0? blue : 0).toString(16);
        while (red.length < 2) red = '0'+red;
        while (blue.length < 2) blue = '0'+blue;
        while (green.length < 2) green = '0'+green;
        dd.elements.slidercanvas.setBgColor("#"+red+green+blue);
    }
}

function my_DropFunc()
{
    if (dd.obj.name == 'button')
    {
        dd.obj.swapImage(outsetbutton.src);
        var minh = dd.elements.button.h+((dd.elements.button.y-dd.elements.greylyr.y)<<1);
        dd.elements.greylyr.resizeTo(
            dd.elements.greylyr.w,
            (dd.elements.greylyr.h != minh)? minh : 300
        );
    }
}


var outsetbutton = new Image();
outsetbutton.src = '../images/dragdrop/button_outset.gif';
var insetbutton = new Image();
insetbutton.src = '../images/dragdrop/button_inset.gif';



var iW = dd.getWndW(),
dh = dd.elements.upleft.h - dd.elements.low.h;
ANIM();

dd.elements.bluelyr.moveTo(
    (iW-dd.elements.bluelyr.w)>>1,
    dd.elements.counter.defy-dd.elements.bluelyr.h-30
);
dd.elements.greylyr.moveTo(dd.elements.sepline1.x-2, dd.elements.sepline1.y+110);
dd.elements.bluelyr.show();
dd.elements.greylyr.show();

dd.elements.thumb.moveTo(dd.elements.track.x+36, dd.elements.track.y);
dd.elements.thumb.setZ(dd.elements.track.z+1);
dd.elements.track.addChild('thumb');
dd.elements.thumb.defx = dd.elements.track.x+36;

//-->
</script>
</body>
</html>