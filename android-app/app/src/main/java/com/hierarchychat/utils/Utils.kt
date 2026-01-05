package com.hierarchychat.utils

import android.content.Context
import android.widget.Toast
import java.text.SimpleDateFormat
import java.util.*

object Utils {
    
    fun showToast(context: Context, message: String) {
        Toast.makeText(context, message, Toast.LENGTH_SHORT).show()
    }
    
    fun getRoleDisplayName(role: String): String {
        return when (role) {
            "super_admin" -> "Super Admin"
            "admin" -> "Admin"
            "manager" -> "Manager"
            "incharge" -> "Incharge"
            "team_leader" -> "Team Leader"
            "employee" -> "Employee"
            else -> role.replaceFirstChar { it.uppercase() }
        }
    }
    
    fun formatChatTime(dateString: String): String {
        return try {
            val inputFormat = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US)
            val outputFormat = SimpleDateFormat("HH:mm", Locale.US)
            val date = inputFormat.parse(dateString.take(19))
            outputFormat.format(date!!)
        } catch (e: Exception) {
            ""
        }
    }
    
    fun getInitials(name: String): String {
        return name.split(" ")
            .take(2)
            .mapNotNull { it.firstOrNull()?.uppercase() }
            .joinToString("")
    }
}
