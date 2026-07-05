package com.bjuka.bjuka_internship_app

import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Environment
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "com.bjuka/certificate")
            .setMethodCallHandler { call, result ->
                val url = call.argument<String>("url")
                if (url.isNullOrBlank()) {
                    result.error("invalid_url", "Certificate URL is missing.", null)
                    return@setMethodCallHandler
                }

                when (call.method) {
                    "openCertificate" -> {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                        startActivity(intent)
                        result.success(null)
                    }
                    "downloadCertificate" -> {
                        val fileName = call.argument<String>("fileName")
                            ?.takeIf { it.isNotBlank() }
                            ?: "BJUKA_Certificate.pdf"
                        val request = DownloadManager.Request(Uri.parse(url))
                            .setTitle(fileName)
                            .setDescription("Downloading certificate")
                            .setNotificationVisibility(
                                DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED
                            )
                            .setDestinationInExternalPublicDir(
                                Environment.DIRECTORY_DOWNLOADS,
                                fileName
                            )
                            .setAllowedOverMetered(true)
                            .setAllowedOverRoaming(true)

                        val manager = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
                        manager.enqueue(request)
                        result.success(null)
                    }
                    "shareCertificate" -> {
                        val intent = Intent(Intent.ACTION_SEND)
                            .setType("text/plain")
                            .putExtra(Intent.EXTRA_SUBJECT, "B. JUKA Technologies Certificate")
                            .putExtra(Intent.EXTRA_TEXT, url)

                        startActivity(Intent.createChooser(intent, "Share certificate"))
                        result.success(null)
                    }
                    else -> result.notImplemented()
                }
            }
    }
}
