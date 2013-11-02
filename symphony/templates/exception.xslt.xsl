<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="data">
	<html>
		<head>
			<title>Symphony XSLT Error</title>
			<link rel="stylesheet" type="text/css" media="screen" href="{$root}/symphony/assets/css/peripheral.css" />
		</head>
		<body>
			<h1>Symphony XSLT Error</h1>
			<div class="panel">
				<h2><xsl:value-of select="details"/></h2>
				<p>
					<xsl:text>An error occurred during the transformation process in </xsl:text>
					<code><xsl:value-of select="details/@file"/></code>
					<xsl:text> around line </xsl:text>
					<code><xsl:value-of select="details/@line"/></code>
					<xsl:text>.</xsl:text>
				</p>
				<ul>
					<xsl:for-each select="nearby-lines/item">
						<li>
							<xsl:if test="position() mod 2 = 0">
								<xsl:attribute name="class">odd</xsl:attribute>
							</xsl:if>
							<xsl:if test="@number = /data/details/@line">
								<xsl:attribute name="id">error</xsl:attribute>
							</xsl:if>
							<span><xsl:value-of select="@number"/></span>
							<code><xsl:value-of disable-output-escaping="yes" select="."/></code>
						</li>
					</xsl:for-each>
				</ul>
			</div>

			<h3>Processor Errors</h3>
			<div class="panel">
				<ul>
					<xsl:for-each select="processing-errors/item">
						<li>
							<xsl:if test="position() mod 2 = 0">
								<xsl:attribute name="class">odd</xsl:attribute>
							</xsl:if>
							<xsl:if test="not(@file = '')">
								<xsl:value-of select="concat('[', @file, ':', @line, ']')"/>
							</xsl:if>
							<xsl:value-of select="."/>
						</li>
					</xsl:for-each>
				</ul>
			</div>

			<h3>Parameters</h3>
			<div class="panel">
				<p>
					<xsl:text>Parameter output for </xsl:text>
					<code><xsl:value-of select='parameters/param[@key = "current-url"]/@value' /></code>
				</p>
				<ul>
					<xsl:for-each select="parameters/param">
						<li>
							<xsl:if test="position() mod 2 = 0"><xsl:attribute name="class">odd</xsl:attribute></xsl:if>
							<code>
								<xsl:value-of select="@value"/>

								<strong>
									<xsl:value-of select="@key"/>
								</strong>
							</code>
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</body>
	</html>
</xsl:template>

</xsl:stylesheet>
