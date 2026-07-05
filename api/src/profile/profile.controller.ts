import { Body, Controller, Delete, Get, HttpCode, HttpStatus, Param, ParseIntPipe, Patch, Post, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { ProfileService } from './profile.service'
import { AddProofDto, AddSkillDto, ResolveProofRequestDto, UpdateProfileDto } from './dto/profile.dto'

/** الملف الخاص — يطابق /profile/* في ../api/openapi.yaml. كله محمي بالتوكن ومملوك للمستخدم. */
@Controller('profile')
@UseGuards(JwtAuthGuard)
export class ProfileController {
  constructor(private readonly profile: ProfileService) {}

  @Get()
  get(@CurrentUser() user: User) {
    return this.profile.getOrCreate(user.id)
  }

  @Patch()
  update(@CurrentUser() user: User, @Body() dto: UpdateProfileDto) {
    return this.profile.update(user.id, dto)
  }

  @Post('skills')
  @HttpCode(HttpStatus.CREATED)
  addSkill(@CurrentUser() user: User, @Body() dto: AddSkillDto) {
    return this.profile.addSkill(user.id, dto)
  }

  @Delete('skills/:skillId')
  @HttpCode(HttpStatus.NO_CONTENT)
  removeSkill(@CurrentUser() user: User, @Param('skillId', ParseIntPipe) skillId: number) {
    return this.profile.removeSkill(user.id, skillId)
  }

  @Post('skills/:skillId/proofs')
  @HttpCode(HttpStatus.CREATED)
  addProof(
    @CurrentUser() user: User,
    @Param('skillId', ParseIntPipe) skillId: number,
    @Body() dto: AddProofDto,
  ) {
    return this.profile.addProof(user.id, skillId, dto)
  }

  @Get('proof-requests')
  proofRequests(@CurrentUser() user: User) {
    return this.profile.listProofRequests(user.id)
  }

  @Post('proof-requests/:id/resolve')
  @HttpCode(HttpStatus.NO_CONTENT)
  resolve(
    @CurrentUser() user: User,
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: ResolveProofRequestDto,
  ) {
    return this.profile.resolveProofRequest(user.id, id, dto.accept)
  }
}
