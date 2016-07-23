<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>JSlint Report</title>
                <style>
                    body table {
                        font-size: 12px;
                        font-family: Arial;
                    }
                    .wrapper {
                        width:60%;
                        margin-left:auto;
                        margin-right:auto;
                    }
                    
                    table.file {
                        width:100%;
                        margin:20px;
                        background-color:#FAF0E6;
                    }
                    
                    table
                    {
                        border-color: #BABDB6;
                        border-width: 0 0 1px 1px;
                        border-style: solid;
                    }
                    th {
                        background-color: #FFF;
                    }
                    td {
                       background-color: #EEEEEC; 
                    }
                    td, th
                    {
                        border-color: #BABDB6;
                        border-width: 1px 1px 0 0;
                        border-style: solid;
                        margin: 0;
                        padding: 4px;
                    }
                    
                    .filename {
                        background-color: #2F3537;
                        color: white;
                        font-weight: bold;
                        font-size: 13px;
                    }
                    
                    td.stripe {
                        background-color: #FFF;
                    }
                </style>
            </head>
            <body>
                <div class="wrapper">
                    <xsl:apply-templates/>
                </div>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="jslint">
        <xsl:apply-templates/>
    </xsl:template>
    <xsl:template match="file">
        <xsl:if test="issue">
            <table class="file" cellspacing="0">
                <tr><td colspan="3" class="filename"><xsl:value-of select="@name"/></td></tr>
                <tr><th width="40px">Line</th><th width="40px">Char</th><th>Reason</th></tr>
                <xsl:apply-templates/>
            </table>
        </xsl:if>
    </xsl:template>
    <xsl:template match="issue">
        <tr><td style="text-align:center"><xsl:value-of select="@line"/></td><td style="text-align:center"><xsl:value-of select="@char"/></td><td><xsl:value-of select="@reason"/></td></tr>
        <tr class="stripe"><td class="stripe"></td><td class="stripe"></td><td class="stripe"><span style="padding-left:30px"><xsl:value-of select="@evidence"/></span></td></tr>
    </xsl:template>
</xsl:stylesheet>