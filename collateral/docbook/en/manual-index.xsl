<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl =
"http://www.w3.org/1999/XSL/Transform" version="1.0">

    <!-- By default copy the whole document -->
    <xsl:template match="node()|@*">
        <xsl:copy>
            <xsl:apply-templates select="node()|@*"/>
        </xsl:copy>
    </xsl:template>

    <!-- Each command is placed twice into index -->
    <xsl:template match="command">
        <!-- Copy original element -->
        <xsl:copy-of select="."/>
        <!-- Create new index entries -->
        <indexterm>
            <primary><xsl:value-of select="."/></primary>
        </indexterm>
        <indexterm>
            <primary>Commands</primary>
            <secondary><xsl:value-of select="."/></secondary>
        </indexterm>
    </xsl:template>

    <!-- Each filename is placed into index -->
    <xsl:template match="filename">
        <!-- Copy original element -->
        <xsl:copy-of select="."/>
        <!-- Create new index entry -->
        <indexterm>
            <primary>Files</primary>
            <secondary><xsl:value-of select="."/></secondary>
        </indexterm>
    </xsl:template>

    <!-- Each acronym is placed into index -->
    <xsl:template match="acronym">
        <!-- Copy original element -->
        <xsl:copy-of select="."/>
        <!-- Create new index entry -->
        <indexterm>
            <primary><xsl:value-of select="."/></primary>
        </indexterm>
        <indexterm>
            <primary>Acronyms</primary>
            <secondary><xsl:value-of select="."/></secondary>
        </indexterm>
    </xsl:template>

    <!-- Index screen names -->
    <xsl:template match="emphasis[@role='screen']">
        <!-- Copy original element -->
        <xsl:copy-of select="."/>
        <!-- Create new index entry -->
        <indexterm>
            <primary>Screens</primary>
            <secondary><xsl:value-of select="."/></secondary>
        </indexterm>
    </xsl:template>

    <!-- Index dialog names -->
    <xsl:template match="emphasis[@role='dialog']">
        <!-- Copy original element -->
        <xsl:copy-of select="."/>
        <!-- Create new index entry -->
        <indexterm>
            <primary>Dialogs</primary>
            <secondary><xsl:value-of select="."/></secondary>
        </indexterm>
    </xsl:template>

    <!-- Index pubsub topics -->
    <xsl:template match="emphasis[@role='pubsub-topic']">
        <!-- Copy original element -->
        <xsl:copy-of select="."/>
        <!-- Create new index entry -->
        <indexterm>
            <primary><xsl:value-of select="."/></primary>
        </indexterm>
        <indexterm>
            <primary>Pub/Sub Topics</primary>
            <secondary><xsl:value-of select="."/></secondary>
        </indexterm>
    </xsl:template>

</xsl:stylesheet>
