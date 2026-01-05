package com.hierarchychat.api

import com.hierarchychat.models.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("login")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<AuthResponse>>

    @POST("logout")
    suspend fun logout(): Response<ApiResponse<Unit>>

    @GET("profile")
    suspend fun getProfile(): Response<ApiResponse<User>>

    @GET("users/messageable")
    suspend fun getMessageableUsers(): Response<ApiResponse<List<UserBrief>>>

    @GET("messages/conversations")
    suspend fun getConversations(): Response<ApiResponse<List<Conversation>>>

    @GET("messages/private/{userId}")
    suspend fun getPrivateMessages(@Path("userId") userId: Long): Response<ApiResponse<List<Message>>>

    @POST("messages/private/{userId}")
    suspend fun sendPrivateMessage(@Path("userId") userId: Long, @Body request: SendMessageRequest): Response<ApiResponse<Message>>

    @GET("messages/group/{groupId}")
    suspend fun getGroupMessages(@Path("groupId") groupId: Long): Response<ApiResponse<List<Message>>>

    @POST("messages/group/{groupId}")
    suspend fun sendGroupMessage(@Path("groupId") groupId: Long, @Body request: SendMessageRequest): Response<ApiResponse<Message>>

    @POST("messages/broadcast")
    suspend fun broadcastMessage(@Body request: SendMessageRequest): Response<ApiResponse<Unit>>
}
