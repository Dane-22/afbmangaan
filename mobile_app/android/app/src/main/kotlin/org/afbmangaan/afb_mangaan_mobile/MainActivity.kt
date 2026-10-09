package org.afbmangaan.afb_mangaan_mobile

import android.app.Activity
import android.content.Context
import android.content.Intent
import android.print.PrintAttributes
import android.print.PrintJob
import android.print.PrintManager
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.result.contract.ActivityResultContracts
import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel
import java.io.ByteArrayOutputStream
import java.util.zip.ZipEntry
import java.util.zip.ZipOutputStream

class MainActivity : FlutterFragmentActivity() {
    private var pendingResult: MethodChannel.Result? = null
    private var pendingBytes: ByteArray? = null
    private var printView: WebView? = null
    private var printJob: PrintJob? = null

    private val documentPicker = registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { response ->
        val result = pendingResult
        val bytes = pendingBytes
        pendingResult = null
        pendingBytes = null
        val uri = response.data?.data
        if (response.resultCode != Activity.RESULT_OK || uri == null || bytes == null) {
            result?.success(false)
        } else {
            Thread {
                try {
                    contentResolver.openOutputStream(uri)?.use { it.write(bytes) }
                        ?: throw IllegalStateException("Could not open the selected file")
                    runOnUiThread { result?.success(true) }
                } catch (error: Exception) {
                    runOnUiThread { result?.error("SAVE_FAILED", error.message, null) }
                }
            }.start()
        }
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "org.afbmangaan.portal/export").setMethodCallHandler { call, result ->
            when (call.method) {
                "saveCsv" -> {
                    val content = call.argument<String>("content") ?: ""
                    saveDocument(call.argument<String>("filename") ?: "report.csv", "text/csv", content.toByteArray(Charsets.UTF_8), result)
                }
                "saveSpreadsheet" -> {
                    val headers = call.argument<List<String>>("headers") ?: emptyList()
                    val rows = call.argument<List<List<String>>>("rows") ?: emptyList()
                    Thread {
                        try {
                            val bytes = spreadsheet(listOf(headers) + rows)
                            runOnUiThread { saveDocument(call.argument<String>("filename") ?: "report.xlsx", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", bytes, result) }
                        } catch (error: Exception) {
                            runOnUiThread { result.error("EXPORT_FAILED", error.message, null) }
                        }
                    }.start()
                }
                "printReport" -> {
                    if (printJob?.let { !it.isCompleted && !it.isCancelled && !it.isFailed } == true) {
                        result.error("PRINT_BUSY", "Finish the current print job first", null)
                    } else {
                        try {
                            printView?.destroy()
                            val view = WebView(this)
                            printView = view
                            view.settings.javaScriptEnabled = false
                            view.settings.blockNetworkLoads = true
                            view.webViewClient = object : WebViewClient() {
                                private var started = false
                                override fun onPageFinished(webView: WebView, url: String) {
                                    if (started) return
                                    started = true
                                    val name = call.argument<String>("title") ?: "AFB Attendance Report"
                                    val manager = getSystemService(Context.PRINT_SERVICE) as PrintManager
                                    printJob = manager.print(name, webView.createPrintDocumentAdapter(name), PrintAttributes.Builder().setMediaSize(PrintAttributes.MediaSize.ISO_A4.asLandscape()).build())
                                    result.success(true)
                                }
                            }
                            view.loadDataWithBaseURL(null, call.argument<String>("html") ?: "", "text/html", "UTF-8", null)
                        } catch (error: Exception) {
                            result.error("PRINT_FAILED", error.message, null)
                        }
                    }
                }
                else -> result.notImplemented()
            }
        }
    }

    private fun saveDocument(filename: String, mime: String, bytes: ByteArray, result: MethodChannel.Result) {
        if (pendingResult != null) {
            result.error("SAVE_BUSY", "Finish saving the current file first", null)
            return
        }
        pendingBytes = bytes
        pendingResult = result
        try {
            documentPicker.launch(Intent(Intent.ACTION_CREATE_DOCUMENT).apply {
                addCategory(Intent.CATEGORY_OPENABLE)
                type = mime
                putExtra(Intent.EXTRA_TITLE, filename)
            })
        } catch (error: Exception) {
            pendingBytes = null
            pendingResult = null
            result.error("SAVE_FAILED", error.message, null)
        }
    }

    private fun xml(value: String): String = value.filter { it == '\n' || it == '\r' || it == '\t' || it.code >= 32 }
        .replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace("\"", "&quot;").replace("'", "&apos;")

    private fun column(index: Int): String {
        var number = index + 1
        var label = ""
        while (number > 0) {
            number--
            label = ('A'.code + number % 26).toChar() + label
            number /= 26
        }
        return label
    }

    private fun spreadsheet(rows: List<List<String>>): ByteArray {
        val output = ByteArrayOutputStream()
        ZipOutputStream(output).use { zip ->
            fun entry(name: String, text: String) {
                zip.putNextEntry(ZipEntry(name))
                zip.write(text.toByteArray(Charsets.UTF_8))
                zip.closeEntry()
            }
            entry("[Content_Types].xml", """<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>""")
            entry("_rels/.rels", """<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>""")
            entry("xl/workbook.xml", """<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Attendance" sheetId="1" r:id="rId1"/></sheets></workbook>""")
            entry("xl/_rels/workbook.xml.rels", """<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>""")
            val sheet = StringBuilder("""<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>""")
            rows.forEachIndexed { rowIndex, row ->
                sheet.append("<row r=\"").append(rowIndex + 1).append("\">")
                row.forEachIndexed { columnIndex, value ->
                    sheet.append("<c r=\"").append(column(columnIndex)).append(rowIndex + 1).append("\" t=\"inlineStr\"><is><t xml:space=\"preserve\">").append(xml(value)).append("</t></is></c>")
                }
                sheet.append("</row>")
            }
            sheet.append("</sheetData></worksheet>")
            entry("xl/worksheets/sheet1.xml", sheet.toString())
        }
        return output.toByteArray()
    }

    override fun onDestroy() {
        printView?.destroy()
        printView = null
        pendingResult?.error("SAVE_CANCELLED", "App closed before the file was saved", null)
        pendingResult = null
        pendingBytes = null
        super.onDestroy()
    }
}
